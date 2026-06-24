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

    /**
     * Stream a response for the given message.
     * For specialised agents (non-chat) we run them fully and then fake-stream
     * the completed text word-by-word so the caller sees a progressive edit.
     * For the generic chat path we use real token streaming from the LLM.
     */
    public function handleStream(User $user, string $message, string $currency): \Generator
    {
        $lower = strtolower($message);

        // For structured agents: run fully then chunk-stream the result
        if ($this->matches($lower, ['health', 'score', 'سلامت', 'امتیاز'])) {
            yield from $this->fakeStream($this->healthAgent->explainScore($user, $currency));
            return;
        }
        if ($this->matches($lower, ['goal', 'save', 'target', 'هدف', 'پس‌انداز', 'خرید'])) {
            yield from $this->fakeStream($this->goalAgent->planGoals($user, $currency));
            return;
        }
        if ($this->matches($lower, ['budget', 'limit', 'بودجه', 'سقف'])) {
            yield from $this->fakeStream($this->budgetAgent->advise($user));
            return;
        }
        if ($this->matches($lower, ['forecast', 'predict', 'future', 'next month', 'پیش‌بینی', 'آینده'])) {
            yield from $this->fakeStream($this->forecastAgent->forecast($user, $currency));
            return;
        }
        if ($this->matches($lower, ['subscription', 'netflix', 'spotify', 'apple', 'google', 'اشتراک'])) {
            yield from $this->fakeStream($this->subscriptionAgent->analyze($user));
            return;
        }
        if ($this->matches($lower, ['report', 'monthly', 'summary', 'گزارش', 'خلاصه', 'ماهانه'])) {
            yield from $this->fakeStream($this->reportAgent->monthlyReport($user, $currency, Carbon::now()));
            return;
        }

        // Default: real token streaming from the LLM
        yield from $this->chatAgent->chatStream($user, $message, $currency);
    }

    /** Yield a fully-generated string in small chunks to simulate streaming. */
    private function fakeStream(string $text): \Generator
    {
        $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        foreach ($words as $word) {
            yield $word;
        }
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
