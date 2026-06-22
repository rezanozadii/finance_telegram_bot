<?php

namespace App\AI;

use App\AI\Agents\BudgetAdvisorAgent;
use App\AI\Agents\ChatAgent;
use App\AI\Agents\FinancialHealthAgent;
use App\AI\Agents\ForecastAgent;
use App\AI\Agents\GoalPlannerAgent;
use App\AI\Agents\ReportWriterAgent;
use App\AI\Agents\SubscriptionAnalyzerAgent;
use App\Models\User;
use Carbon\Carbon;

class AgentOrchestrator
{
    public function __construct(
        private FinancialHealthAgent $healthAgent,
        private GoalPlannerAgent $goalAgent,
        private BudgetAdvisorAgent $budgetAgent,
        private ForecastAgent $forecastAgent,
        private SubscriptionAnalyzerAgent $subscriptionAgent,
        private ReportWriterAgent $reportAgent,
        private ChatAgent $chatAgent,
    ) {}

    public function handle(User $user, string $message, string $currency): string
    {
        $lower = strtolower($message);

        return match (true) {
            $this->matches($lower, ['health', 'score', 'سلامت', 'امتیاز'])
                => $this->healthAgent->explainScore($user, $currency),

            $this->matches($lower, ['goal', 'save', 'target', 'هدف', 'پس‌انداز', 'خرید'])
                => $this->goalAgent->planGoals($user, $currency),

            $this->matches($lower, ['budget', 'limit', 'بودجه', 'سقف'])
                => $this->budgetAgent->advise($user),

            $this->matches($lower, ['forecast', 'predict', 'future', 'next month', 'پیش‌بینی', 'آینده'])
                => $this->forecastAgent->forecast($user, $currency),

            $this->matches($lower, ['subscription', 'netflix', 'spotify', 'apple', 'google', 'اشتراک'])
                => $this->subscriptionAgent->analyze($user),

            $this->matches($lower, ['report', 'monthly', 'summary', 'گزارش', 'خلاصه', 'ماهانه'])
                => $this->reportAgent->monthlyReport($user, $currency, Carbon::now()),

            default
                => $this->chatAgent->chat($user, $message, $currency),
        };
    }

    private function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
