<?php

namespace App\Services\AI;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialCalculatorService
{
    public function getStats(User $user, Carbon $start, Carbon $end, string $currency): array
    {
        $endOfDay = $end->copy()->endOfDay();

        $base = Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->whereBetween('occurred_at', [$start, $endOfDay]);

        $income   = (float) (clone $base)->where('type', 'income')->sum('amount');
        $expenses = (float) (clone $base)->where('type', 'expense')->sum('amount');
        $count    = (clone $base)->whereIn('type', ['income', 'expense'])->count();

        $days        = max(1, $start->diffInDays($endOfDay));
        $savingsRate = $income > 0 ? round(($income - $expenses) / $income * 100, 1) : 0.0;
        $avgDaily    = round($expenses / $days, 2);

        $categoryRows = (clone $base)
            ->where('type', 'expense')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $categories = Category::where('user_id', $user->id)->get()->keyBy('id');

        $topCategories = $categoryRows->map(fn ($row) => [
            'name'   => $categories->get($row->category_id)?->name ?? 'Uncategorized',
            'icon'   => $categories->get($row->category_id)?->icon,
            'amount' => (float) $row->total,
            'pct'    => $expenses > 0 ? round((float) $row->total / $expenses * 100, 1) : 0.0,
        ])->values()->all();

        $byDay = (clone $base)
            ->whereIn('type', ['income', 'expense'])
            ->select(
                DB::raw('DATE(occurred_at) as day'),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('day', 'type')
            ->orderBy('day')
            ->get()
            ->groupBy('day')
            ->map(fn ($rows) => [
                'income'   => (float) ($rows->firstWhere('type', 'income')?->total ?? 0),
                'expenses' => (float) ($rows->firstWhere('type', 'expense')?->total ?? 0),
            ])
            ->all();

        return [
            'income'            => $income,
            'expenses'          => $expenses,
            'net'               => $income - $expenses,
            'savings_rate'      => $savingsRate,
            'avg_daily_expense' => $avgDaily,
            'transaction_count' => $count,
            'top_categories'    => $topCategories,
            'by_day'            => $byDay,
            'currency'          => $currency,
            'period_start'      => $start->toDateString(),
            'period_end'        => $end->toDateString(),
        ];
    }

    public function getMonthlyTrend(User $user, int $months, string $currency): array
    {
        $trend     = [];
        $userStart = $user->created_at->copy()->startOfMonth();

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = Carbon::now()->subMonthsNoOverflow($i)->startOfMonth();

            // Skip months that predate account creation — they would only add empty zeros
            if ($start->lt($userStart)) {
                continue;
            }

            $end    = Carbon::now()->subMonthsNoOverflow($i)->endOfMonth();
            $stats  = $this->getStats($user, $start, $end, $currency);

            $trend[] = [
                'month'    => $start->format('Y-m'),
                'label'    => $start->format('M Y'),
                'income'   => $stats['income'],
                'expenses' => $stats['expenses'],
                'net'      => $stats['net'],
            ];
        }

        return $trend;
    }

    public function getTotalBalance(User $user): array
    {
        return $user->accounts()
            ->where('is_archived', false)
            ->get()
            ->groupBy('currency')
            ->map(fn ($accounts) => (float) $accounts->sum('current_balance'))
            ->all();
    }

    public function getNetWorth(User $user, string $baseCurrency): float
    {
        $balances = $this->getTotalBalance($user);
        return (float) ($balances[$baseCurrency] ?? 0.0);
    }
}
