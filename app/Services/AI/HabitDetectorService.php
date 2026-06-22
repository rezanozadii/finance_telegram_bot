<?php

namespace App\Services\AI;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class HabitDetectorService
{
    private const MIN_TRANSACTIONS = 3;

    public function detect(User $user, string $currency): array
    {
        $start = Carbon::now()->subMonths(6)->startOfMonth();

        $merchantGroups = Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->where('occurred_at', '>=', $start)
            ->whereNotNull('merchant')
            ->get()
            ->groupBy(fn ($t) => strtolower(trim($t->merchant ?? '')));

        $habits = [];
        $monthsSpan = 6;

        foreach ($merchantGroups as $merchant => $txns) {
            if ($txns->count() < self::MIN_TRANSACTIONS) {
                continue;
            }

            $totalAmount  = (float) $txns->sum('amount');
            $avgAmount    = round($totalAmount / $txns->count(), 2);
            $monthlyCost  = round($totalAmount / $monthsSpan, 2);
            $yearlyCost   = round($monthlyCost * 12, 2);
            $frequency    = round($txns->count() / $monthsSpan, 1);
            $firstSeen    = $txns->min('occurred_at');
            $lastSeen     = $txns->max('occurred_at');

            $habits[] = [
                'merchant'         => ucfirst($merchant),
                'transaction_count'=> $txns->count(),
                'frequency'        => $frequency,
                'avg_amount'       => $avgAmount,
                'monthly_cost'     => $monthlyCost,
                'yearly_cost'      => $yearlyCost,
                'first_seen'       => Carbon::parse($firstSeen)->toDateString(),
                'last_seen'        => Carbon::parse($lastSeen)->toDateString(),
                'currency'         => $currency,
            ];
        }

        usort($habits, fn ($a, $b) => $b['monthly_cost'] <=> $a['monthly_cost']);

        return $habits;
    }
}
