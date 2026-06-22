<?php

namespace App\Services\AI;

use App\Models\User;
use Carbon\Carbon;

class ForecastingService
{
    public function __construct(private FinancialCalculatorService $calculator) {}

    public function forecast(User $user, string $currency): array
    {
        $trend = $this->calculator->getMonthlyTrend($user, 3, $currency);

        $avgExpense = count($trend) > 0 ? array_sum(array_column($trend, 'expenses')) / count($trend) : 0;
        $avgIncome  = count($trend) > 0 ? array_sum(array_column($trend, 'income')) / count($trend) : 0;

        $balances        = $this->calculator->getTotalBalance($user);
        $currentBalance  = (float) ($balances[$currency] ?? 0);

        $now           = Carbon::now();
        $daysInMonth   = $now->daysInMonth;
        $dayOfMonth    = $now->day;
        $daysRemaining = $daysInMonth - $dayOfMonth;

        $dailyExpense = $daysInMonth > 0 ? $avgExpense / $daysInMonth : 0;
        $dailyIncome  = $daysInMonth > 0 ? $avgIncome / $daysInMonth : 0;

        $projectedEomBalance = $currentBalance + ($dailyIncome * $daysRemaining) - ($dailyExpense * $daysRemaining);
        $savingsPotential    = $avgIncome - $avgExpense;
        $overspendingRisk    = $avgExpense > $avgIncome;

        $goals             = $user->goals()->where('status', 'active')->get();
        $monthsUntilGoals  = [];
        foreach ($goals as $goal) {
            $remaining = $goal->remaining();
            if ($savingsPotential > 0) {
                $months = ceil($remaining / $savingsPotential);
                $monthsUntilGoals[] = [
                    'goal'    => $goal->name,
                    'months'  => (int) $months,
                    'date'    => Carbon::now()->addMonths((int) $months)->format('M Y'),
                    'remaining' => $remaining,
                ];
            } else {
                $monthsUntilGoals[] = [
                    'goal'      => $goal->name,
                    'months'    => null,
                    'date'      => null,
                    'remaining' => $remaining,
                ];
            }
        }

        return [
            'projected_monthly_expense' => round($avgExpense, 2),
            'projected_monthly_income'  => round($avgIncome, 2),
            'projected_eom_balance'     => round($projectedEomBalance, 2),
            'savings_potential'         => round($savingsPotential, 2),
            'overspending_risk'         => $overspendingRisk,
            'current_balance'           => $currentBalance,
            'days_remaining_in_month'   => $daysRemaining,
            'months_until_goals_met'    => $monthsUntilGoals,
            'currency'                  => $currency,
            'monthly_trend'             => $trend,
        ];
    }
}
