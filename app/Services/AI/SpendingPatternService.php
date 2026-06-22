<?php

namespace App\Services\AI;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SpendingPatternService
{
    public function detect(User $user, string $currency): array
    {
        $start = Carbon::now()->subMonths(3)->startOfMonth();
        $end   = Carbon::now()->endOfDay();

        $transactions = Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->get();

        if ($transactions->isEmpty()) {
            return $this->emptyPatterns();
        }

        $weekendExpenses = $transactions->filter(fn ($t) => Carbon::parse($t->occurred_at)->isWeekend());
        $weekdayExpenses = $transactions->filter(fn ($t) => !Carbon::parse($t->occurred_at)->isWeekend());

        $weekendAvg = $weekendExpenses->isNotEmpty() ? $weekendExpenses->avg('amount') : 0;
        $weekdayAvg = $weekdayExpenses->isNotEmpty() ? $weekdayExpenses->avg('amount') : 0;

        $nightTxns = $transactions->filter(function ($t) {
            $hour = Carbon::parse($t->occurred_at)->hour;
            return $hour >= 22 || $hour < 4;
        });

        $merchantCounts = $transactions->whereNotNull('merchant')
            ->groupBy('merchant')
            ->map(fn ($g) => $g->count())
            ->sortDesc();

        $topMerchant = $merchantCounts->isNotEmpty()
            ? ['name' => $merchantCounts->keys()->first(), 'count' => $merchantCounts->first()]
            : null;

        $byDayOfMonth = $transactions->groupBy(fn ($t) => Carbon::parse($t->occurred_at)->day)
            ->map(fn ($g) => $g->sum('amount'))
            ->sortDesc();

        $salaryDaySpike = $byDayOfMonth->isNotEmpty()
            ? ['day' => $byDayOfMonth->keys()->first(), 'amount' => $byDayOfMonth->first()]
            : null;

        $avgTransaction   = round($transactions->avg('amount'), 2);
        $impulseCount     = $transactions->where('amount', '<', 5)->count();
        $microCount       = $transactions->where('amount', '<', 2)->count();
        $totalAmount      = $transactions->sum('amount');

        return [
            'weekend_spending' => [
                'weekend_avg'          => round($weekendAvg, 2),
                'weekday_avg'          => round($weekdayAvg, 2),
                'weekend_higher'       => $weekendAvg > $weekdayAvg,
                'ratio'                => $weekdayAvg > 0 ? round($weekendAvg / $weekdayAvg, 2) : 1.0,
            ],
            'night_spending' => [
                'count'      => $nightTxns->count(),
                'total'      => round($nightTxns->sum('amount'), 2),
                'pct_of_all' => $transactions->count() > 0 ? round($nightTxns->count() / $transactions->count() * 100, 1) : 0,
            ],
            'top_merchant'        => $topMerchant,
            'salary_day_spike'    => $salaryDaySpike,
            'avg_transaction'     => $avgTransaction,
            'impulse_transactions'=> $impulseCount,
            'micro_transactions'  => $microCount,
            'total_transactions'  => $transactions->count(),
            'total_spent'         => round($totalAmount, 2),
            'currency'            => $currency,
        ];
    }

    private function emptyPatterns(): array
    {
        return [
            'weekend_spending'    => ['weekend_avg' => 0, 'weekday_avg' => 0, 'weekend_higher' => false, 'ratio' => 1.0],
            'night_spending'      => ['count' => 0, 'total' => 0, 'pct_of_all' => 0],
            'top_merchant'        => null,
            'salary_day_spike'    => null,
            'avg_transaction'     => 0,
            'impulse_transactions'=> 0,
            'micro_transactions'  => 0,
            'total_transactions'  => 0,
            'total_spent'         => 0,
            'currency'            => 'USD',
        ];
    }
}
