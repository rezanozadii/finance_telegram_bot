<?php

namespace App\Services\AI;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class BudgetAnalysisService
{
    public function analyze(User $user): array
    {
        $budgets = Budget::where('user_id', $user->id)->with('category')->get();
        $results = [];
        $now     = Carbon::now();

        foreach ($budgets as $budget) {
            [$start, $end] = $this->periodBounds($budget->period, $now);

            $query = Transaction::where('user_id', $user->id)
                ->where('currency', $budget->currency)
                ->where('type', 'expense')
                ->whereBetween('occurred_at', [$start, $end->copy()->endOfDay()]);

            if ($budget->category_id) {
                $query->where('category_id', $budget->category_id);
            }

            $spent     = (float) $query->sum('amount');
            $limit     = (float) $budget->amount;
            $pctUsed   = $limit > 0 ? round($spent / $limit * 100, 1) : 0;

            $totalDays     = max(1, $start->diffInDays($end) + 1);
            $elapsedDays   = max(1, $start->diffInDays($now) + 1);
            $remainingDays = max(0, $now->diffInDays($end));

            $projectedTotal = $elapsedDays > 0
                ? round($spent / $elapsedDays * $totalDays, 2)
                : $spent;

            $status = match (true) {
                $pctUsed >= 100 => 'exceeded',
                $pctUsed >= 90  => 'critical',
                $pctUsed >= 80  => 'warning',
                default         => 'safe',
            };

            $results[] = [
                'id'              => $budget->id,
                'name'            => $budget->name,
                'category'        => $budget->category?->name,
                'amount'          => $limit,
                'spent'           => $spent,
                'remaining'       => max(0, $limit - $spent),
                'pct_used'        => $pctUsed,
                'status'          => $status,
                'days_remaining'  => $remainingDays,
                'projected_total' => $projectedTotal,
                'currency'        => $budget->currency,
                'period'          => $budget->period,
            ];
        }

        return $results;
    }

    private function periodBounds(string $period, Carbon $now): array
    {
        return match ($period) {
            'weekly'  => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'yearly'  => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
