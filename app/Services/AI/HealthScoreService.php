<?php

namespace App\Services\AI;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class HealthScoreService
{
    public function __construct(
        private FinancialCalculatorService $calculator,
        private BudgetAnalysisService $budgetAnalysis,
    ) {}

    public function calculate(User $user, string $currency): array
    {
        $now        = Carbon::now();
        $start      = $now->copy()->subMonths(3)->startOfMonth();
        $end        = $now->copy()->endOfMonth();
        $stats      = $this->calculator->getStats($user, $start, $end, $currency);
        $trend      = $this->calculator->getMonthlyTrend($user, 6, $currency);
        $budgets    = $this->budgetAnalysis->analyze($user);
        $accounts   = $user->accounts()->where('is_archived', false)->get();

        $components = [];

        // Savings Rate (weight 20)
        $savingsRate   = (float) $stats['savings_rate'];
        $srScore       = (int) min(100, $savingsRate * 4);
        $components[]  = [
            'key'            => 'savings_rate',
            'label'          => __('health.savings_rate'),
            'score'          => $srScore,
            'weight'         => 20,
            'weighted_score' => round($srScore * 20 / 100, 1),
            'value'          => $savingsRate . '%',
            'explanation'    => __('health.exp_savings_rate', ['pct' => $savingsRate]),
        ];

        // Expense Consistency (weight 15)
        $monthlyExpenses = array_column($trend, 'expenses');
        $ecScore         = $this->consistencyScore($monthlyExpenses);
        $components[]    = [
            'key'            => 'expense_consistency',
            'label'          => __('health.expense_consistency'),
            'score'          => $ecScore,
            'weight'         => 15,
            'weighted_score' => round($ecScore * 15 / 100, 1),
            'explanation'    => __('health.exp_expense_consistency'),
        ];

        // Budget Compliance (weight 15)
        $budgetScore = 100;
        if (!empty($budgets)) {
            $exceeded    = count(array_filter($budgets, fn ($b) => $b['status'] === 'exceeded'));
            $budgetScore = (int) max(0, 100 - ($exceeded / count($budgets)) * 100);
        }
        $components[] = [
            'key'            => 'budget_compliance',
            'label'          => __('health.budget_compliance'),
            'score'          => $budgetScore,
            'weight'         => 15,
            'weighted_score' => round($budgetScore * 15 / 100, 1),
            'explanation'    => count($budgets) > 0
                ? __('health.exp_budget_compliance_set', ['pct' => $budgetScore])
                : __('health.exp_budget_compliance_none'),
        ];

        // Cash Flow (weight 15)
        $net    = (float) $stats['net'];
        $cfScore = $net >= 0 ? min(100, (int) (50 + $savingsRate * 2)) : max(0, 50 + (int) ($net / 100));
        $cfScore = max(0, min(100, $cfScore));
        $components[] = [
            'key'            => 'cash_flow',
            'label'          => __('health.cash_flow'),
            'score'          => $cfScore,
            'weight'         => 15,
            'weighted_score' => round($cfScore * 15 / 100, 1),
            'value'          => ($net >= 0 ? '+' : '') . number_format($net, 2) . ' ' . $currency,
            'explanation'    => $net >= 0
                ? __('health.exp_cash_flow_positive')
                : __('health.exp_cash_flow_negative'),
        ];

        // Income Stability (weight 10)
        $monthlyIncome = array_column($trend, 'income');
        $isScore       = $this->consistencyScore($monthlyIncome);
        $components[]  = [
            'key'            => 'income_stability',
            'label'          => __('health.income_stability'),
            'score'          => $isScore,
            'weight'         => 10,
            'weighted_score' => round($isScore * 10 / 100, 1),
            'explanation'    => __('health.exp_income_stability'),
        ];

        // Emergency Fund (weight 10)
        $liquidBalance = (float) $accounts->whereIn('type', ['cash', 'bank', 'e-wallet'])->sum('current_balance');
        $avgMonthlyExp = $stats['avg_daily_expense'] * 30;
        $monthsCovered = $avgMonthlyExp > 0 ? $liquidBalance / $avgMonthlyExp : 0;
        $efScore       = (int) min(100, $monthsCovered / 6 * 100);
        $components[]  = [
            'key'            => 'emergency_fund',
            'label'          => __('health.emergency_fund'),
            'score'          => $efScore,
            'weight'         => 10,
            'weighted_score' => round($efScore * 10 / 100, 1),
            'value'          => round($monthsCovered, 1) . ' months',
            'explanation'    => __('health.exp_emergency_fund', ['months' => round($monthsCovered, 1)]),
        ];

        // Recurring Ratio (weight 10)
        $recurringExpenses = (float) Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->where('source', 'recurring')
            ->whereBetween('occurred_at', [$start, $end->copy()->endOfDay()])
            ->sum('amount');
        $recurringPct = $stats['expenses'] > 0 ? $recurringExpenses / $stats['expenses'] * 100 : 0;
        $rrScore      = (int) max(0, 100 - max(0, $recurringPct - 30) * 2);
        $components[] = [
            'key'            => 'recurring_ratio',
            'label'          => __('health.recurring_ratio'),
            'score'          => $rrScore,
            'weight'         => 10,
            'weighted_score' => round($rrScore * 10 / 100, 1),
            'value'          => round($recurringPct, 1) . '%',
            'explanation'    => __('health.exp_recurring_ratio', ['pct' => round($recurringPct, 1)]),
        ];

        // Transaction Diversity (weight 5)
        $uniqueCategories = Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end->copy()->endOfDay()])
            ->whereNotNull('category_id')
            ->distinct('category_id')
            ->count('category_id');
        $tdScore      = (int) min(100, $uniqueCategories * 10);
        $components[] = [
            'key'            => 'transaction_diversity',
            'label'          => __('health.transaction_diversity'),
            'score'          => $tdScore,
            'weight'         => 5,
            'weighted_score' => round($tdScore * 5 / 100, 1),
            'value'          => $uniqueCategories . ' categories',
            'explanation'    => __('health.exp_transaction_diversity', ['count' => $uniqueCategories]),
        ];

        $totalScore = (int) array_sum(array_column($components, 'weighted_score'));
        $grade      = $this->grade($totalScore);

        return [
            'total'       => $totalScore,
            'personality' => __('health.personality_' . $grade),
            'components'  => collect($components)->keyBy('key')->toArray(),
            'currency'    => $currency,
        ];
    }

    private function consistencyScore(array $values): int
    {
        if (count($values) < 2) {
            return 80;
        }

        $avg = array_sum($values) / count($values);
        if ($avg == 0) {
            return 100;
        }

        $variance = array_sum(array_map(fn ($v) => pow($v - $avg, 2), $values)) / count($values);
        $stdDev   = sqrt($variance);
        $cv       = $stdDev / $avg;

        return (int) max(0, min(100, 100 - $cv * 100));
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 55 => 'C',
            $score >= 40 => 'D',
            default      => 'F',
        };
    }
}
