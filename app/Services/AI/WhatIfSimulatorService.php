<?php

namespace App\Services\AI;

use App\Models\User;
use Carbon\Carbon;

class WhatIfSimulatorService
{
    public function __construct(
        private FinancialCalculatorService $calculator,
        private ForecastingService $forecasting,
        private SubscriptionDetectorService $subscriptionDetector,
    ) {}

    public function simulate(User $user, string $scenario, array $params, string $currency): array
    {
        return match ($scenario) {
            'reduce_category'    => $this->reduceCategory($user, $params, $currency),
            'salary_increase'    => $this->salaryIncrease($user, $params, $currency),
            'save_fixed'         => $this->saveFixed($user, $params, $currency),
            'cancel_subscription'=> $this->cancelSubscription($user, $params, $currency),
            default              => ['error' => 'Unknown scenario'],
        };
    }

    private function reduceCategory(User $user, array $params, string $currency): array
    {
        $start        = Carbon::now()->subMonths(3)->startOfMonth();
        $end          = Carbon::now()->endOfMonth();
        $stats        = $this->calculator->getStats($user, $start, $end, $currency);
        $reductionPct = (float) ($params['reduction_pct'] ?? 10);
        $categoryName = $params['category_name'] ?? '';

        $targetCategory = collect($stats['top_categories'])
            ->firstWhere('name', $categoryName) ?? $stats['top_categories'][0] ?? null;

        if (!$targetCategory) {
            return ['error' => 'Category not found'];
        }

        $monthlySpend    = $targetCategory['amount'] / 3;
        $monthlySavings  = $monthlySpend * $reductionPct / 100;
        $yearlySavings   = $monthlySavings * 12;

        $goals           = $user->goals()->where('status', 'active')->get();
        $currentSavings  = max(0, ($stats['income'] - $stats['expenses']) / 3);
        $newSavings      = $currentSavings + $monthlySavings;

        $goalImpact = $goals->map(fn ($g) => [
            'goal'             => $g->name,
            'remaining'        => $g->remaining(),
            'months_now'       => $currentSavings > 0 ? ceil($g->remaining() / $currentSavings) : null,
            'months_with_change'=> $newSavings > 0 ? ceil($g->remaining() / $newSavings) : null,
        ])->values()->all();

        return [
            'scenario'        => 'reduce_category',
            'category'        => $categoryName,
            'reduction_pct'   => $reductionPct,
            'monthly_savings' => round($monthlySavings, 2),
            'yearly_savings'  => round($yearlySavings, 2),
            'goal_impact'     => $goalImpact,
            'currency'        => $currency,
        ];
    }

    private function salaryIncrease(User $user, array $params, string $currency): array
    {
        $start       = Carbon::now()->subMonths(3)->startOfMonth();
        $end         = Carbon::now()->endOfMonth();
        $stats       = $this->calculator->getStats($user, $start, $end, $currency);
        $increasePct = (float) ($params['increase_pct'] ?? 10);

        $avgMonthlyIncome  = $stats['income'] / 3;
        $avgMonthlyExpense = $stats['expenses'] / 3;

        $newMonthlyIncome = $avgMonthlyIncome * (1 + $increasePct / 100);
        $currentSavings   = max(0, $avgMonthlyIncome - $avgMonthlyExpense);
        $newSavings       = max(0, $newMonthlyIncome - $avgMonthlyExpense);
        $additionalMonthly= $newSavings - $currentSavings;

        $goals      = $user->goals()->where('status', 'active')->get();
        $goalImpact = $goals->map(fn ($g) => [
            'goal'              => $g->name,
            'remaining'         => $g->remaining(),
            'months_now'        => $currentSavings > 0 ? ceil($g->remaining() / $currentSavings) : null,
            'months_with_change'=> $newSavings > 0 ? ceil($g->remaining() / $newSavings) : null,
        ])->values()->all();

        return [
            'scenario'           => 'salary_increase',
            'increase_pct'       => $increasePct,
            'new_monthly_income' => round($newMonthlyIncome, 2),
            'new_monthly_net'    => round($newMonthlyIncome - $avgMonthlyExpense, 2),
            'additional_monthly' => round($additionalMonthly, 2),
            'yearly_gain'        => round($additionalMonthly * 12, 2),
            'goal_impact'        => $goalImpact,
            'currency'           => $currency,
        ];
    }

    private function saveFixed(User $user, array $params, string $currency): array
    {
        $fixedAmount = (float) ($params['amount'] ?? 100);
        $goals       = $user->goals()->where('status', 'active')->get();

        $goalDates = $goals->map(fn ($g) => [
            'goal'           => $g->name,
            'remaining'      => $g->remaining(),
            'months_needed'  => $fixedAmount > 0 ? (int) ceil($g->remaining() / $fixedAmount) : null,
            'completion_date'=> $fixedAmount > 0
                ? Carbon::now()->addMonths((int) ceil($g->remaining() / $fixedAmount))->format('M Y')
                : null,
        ])->values()->all();

        return [
            'scenario'      => 'save_fixed',
            'monthly_amount'=> $fixedAmount,
            'yearly_amount' => $fixedAmount * 12,
            'goal_dates'    => $goalDates,
            'currency'      => $currency,
        ];
    }

    private function cancelSubscription(User $user, array $params, string $currency): array
    {
        $merchant       = strtolower($params['merchant'] ?? '');
        $subscriptions  = $this->subscriptionDetector->detect($user);

        $found = collect($subscriptions)->first(
            fn ($s) => strtolower($s['merchant']) === $merchant
        );

        if (!$found) {
            return ['error' => "Subscription '{$params['merchant']}' not found", 'currency' => $currency];
        }

        $monthly = $found['monthly_cost'];
        $yearly  = $found['yearly_cost'];

        $goals      = $user->goals()->where('status', 'active')->get();
        $goalImpact = $goals->map(fn ($g) => [
            'goal'           => $g->name,
            'remaining'      => $g->remaining(),
            'months_to_goal' => $monthly > 0 ? ceil($g->remaining() / $monthly) : null,
        ])->values()->all();

        return [
            'scenario'       => 'cancel_subscription',
            'merchant'       => ucfirst($merchant),
            'monthly_savings'=> $monthly,
            'yearly_savings' => $yearly,
            'goal_impact'    => $goalImpact,
            'currency'       => $currency,
        ];
    }
}
