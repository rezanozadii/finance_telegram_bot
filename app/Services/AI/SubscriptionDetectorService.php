<?php

namespace App\Services\AI;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionDetectorService
{
    private const AMOUNT_TOLERANCE = 0.05;
    private const MONTHLY_DAYS     = [28, 29, 30, 31, 32];
    private const YEARLY_DAYS      = [360, 361, 362, 363, 364, 365, 366, 367, 368, 369, 370];

    public function detect(User $user): array
    {
        $cutoff = Carbon::now()->subYear();

        $merchantGroups = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $cutoff)
            ->whereNotNull('merchant')
            ->get()
            ->groupBy(fn ($t) => strtolower(trim($t->merchant ?? '')));

        $subscriptions = [];

        foreach ($merchantGroups as $merchant => $txns) {
            if ($txns->count() < 2) {
                continue;
            }

            $txns    = $txns->sortBy('occurred_at')->values();
            $result  = $this->analyzeIntervals($merchant, $txns);

            if ($result !== null) {
                $subscriptions[] = $result;
            }
        }

        usort($subscriptions, fn ($a, $b) => $b['monthly_cost'] <=> $a['monthly_cost']);

        return $subscriptions;
    }

    private function analyzeIntervals(string $merchant, \Illuminate\Support\Collection $txns): ?array
    {
        $intervals   = [];
        $amountDiffs = [];

        for ($i = 1; $i < $txns->count(); $i++) {
            $prev     = $txns[$i - 1];
            $curr     = $txns[$i];
            $days     = Carbon::parse($prev->occurred_at)->diffInDays(Carbon::parse($curr->occurred_at));
            $amtDiff  = abs((float) $curr->amount - (float) $prev->amount);
            $amtRatio = (float) $prev->amount > 0 ? $amtDiff / (float) $prev->amount : 1;

            $intervals[]   = $days;
            $amountDiffs[] = $amtRatio;
        }

        $avgInterval = array_sum($intervals) / count($intervals);
        $maxAmtDiff  = max($amountDiffs);

        if ($maxAmtDiff > self::AMOUNT_TOLERANCE) {
            return null;
        }

        $frequency = null;
        if ($this->inRange($avgInterval, self::MONTHLY_DAYS)) {
            $frequency = 'monthly';
        } elseif ($this->inRange($avgInterval, self::YEARLY_DAYS)) {
            $frequency = 'yearly';
        } elseif ($avgInterval >= 6 && $avgInterval <= 8) {
            $frequency = 'weekly';
        }

        if ($frequency === null) {
            return null;
        }

        $last       = $txns->last();
        $amount     = (float) $last->amount;
        $currency   = $last->currency;
        $lastDate   = Carbon::parse($last->occurred_at)->toDateString();
        $nextDate   = match ($frequency) {
            'monthly' => Carbon::parse($last->occurred_at)->addMonth()->toDateString(),
            'yearly'  => Carbon::parse($last->occurred_at)->addYear()->toDateString(),
            'weekly'  => Carbon::parse($last->occurred_at)->addWeek()->toDateString(),
        };
        $monthlyCost = match ($frequency) {
            'monthly' => $amount,
            'yearly'  => round($amount / 12, 2),
            'weekly'  => round($amount * 52 / 12, 2),
        };

        return [
            'merchant'          => ucfirst($merchant),
            'amount'            => $amount,
            'currency'          => $currency,
            'frequency'         => $frequency,
            'last_payment_at'   => $lastDate,
            'next_predicted_at' => $nextDate,
            'monthly_cost'      => $monthlyCost,
            'yearly_cost'       => round($monthlyCost * 12, 2),
            'transaction_count' => $txns->count(),
        ];
    }

    private function inRange(float $value, array $range): bool
    {
        return $value >= min($range) && $value <= max($range);
    }
}
