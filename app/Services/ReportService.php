<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate income/expense summary for a period, in a single currency.
     *
     * @return array{
     *   income: float, expenses: float, net: float, count: int,
     *   by_category: list<array{name:string, icon:string|null, amount:float, pct:float}>,
     *   other_currencies: list<string>
     * }
     */
    public function generate(User $user, Carbon $start, Carbon $end, string $currency): array
    {
        $endOfDay = $end->copy()->endOfDay();

        $base = Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->whereBetween('occurred_at', [$start, $endOfDay]);

        $income   = (float) (clone $base)->where('type', 'income')->sum('amount');
        $expenses = (float) (clone $base)->where('type', 'expense')->sum('amount');
        $count    = (clone $base)->whereIn('type', ['income', 'expense'])->count();

        // Expenses grouped by category — one query
        $rows = (clone $base)
            ->where('type', 'expense')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        // Load all user categories in one shot
        $categories = Category::where('user_id', $user->id)
            ->get()
            ->keyBy('id');

        $byCategory = $rows->map(function ($row) use ($categories, $expenses) {
            $cat = $categories->get($row->category_id);
            return [
                'name'   => $cat?->name ?? 'Uncategorized',
                'icon'   => $cat?->icon,
                'amount' => (float) $row->total,
                'pct'    => $expenses > 0 ? round((float) $row->total / $expenses * 100, 1) : 0.0,
            ];
        })->values()->all();

        // Check if there are transactions in other currencies
        $otherCurrencies = Transaction::where('user_id', $user->id)
            ->where('currency', '!=', $currency)
            ->whereBetween('occurred_at', [$start, $endOfDay])
            ->whereIn('type', ['income', 'expense'])
            ->distinct()
            ->pluck('currency')
            ->all();

        return [
            'income'           => $income,
            'expenses'         => $expenses,
            'net'              => $income - $expenses,
            'count'            => $count,
            'by_category'      => $byCategory,
            'other_currencies' => $otherCurrencies,
        ];
    }

    /**
     * Return [start, end] for a named period relative to now.
     */
    public function periodBounds(string $type, ?string $param = null): array
    {
        $now = Carbon::now();

        return match ($type) {
            'month'      => [
                Carbon::parse($param ?? $now->format('Y-m'))->startOfMonth(),
                Carbon::parse($param ?? $now->format('Y-m'))->endOfMonth(),
            ],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'quarter'    => [
                $now->copy()->startOfQuarter(),
                $now->copy()->endOfQuarter(),
            ],
            'year'       => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ],
            default      => throw new \InvalidArgumentException("Unknown period type: {$type}"),
        };
    }

    /**
     * Return [start, end] for the period immediately before the given window.
     */
    public function previousPeriodBounds(string $type, Carbon $start, Carbon $end): array
    {
        return match ($type) {
            'month'      => [
                $start->copy()->subMonthNoOverflow()->startOfMonth(),
                $start->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_month' => [
                $start->copy()->subMonthNoOverflow()->startOfMonth(),
                $start->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'quarter'    => [
                $start->copy()->subQuarter()->startOfQuarter(),
                $start->copy()->subQuarter()->endOfQuarter(),
            ],
            'year'       => [
                $start->copy()->subYear()->startOfYear(),
                $start->copy()->subYear()->endOfYear(),
            ],
            default      => [
                // custom: same window length shifted back
                $start->copy()->subDays($start->diffInDays($end) + 1),
                $start->copy()->subDay(),
            ],
        };
    }

    public function periodLabel(string $type, Carbon $start, Carbon $end): string
    {
        return match ($type) {
            'month', 'last_month' => $start->format('F Y'),
            'quarter'             => 'Q' . $start->quarter . ' ' . $start->year,
            'year'                => (string) $start->year,
            default               => $start->format('d M Y') . ' – ' . $end->format('d M Y'),
        };
    }

    public function formatChange(float $current, float $previous): string
    {
        if ($previous == 0.0) {
            return $current > 0 ? '↑ new' : '—';
        }

        $pct = ($current - $previous) / $previous * 100;
        $arrow = $pct >= 0 ? '↑' : '↓';
        return sprintf('%s %+.1f%%', $arrow, $pct);
    }
}
