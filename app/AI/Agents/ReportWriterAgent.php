<?php

namespace App\AI\Agents;

use App\AI\Tools\GetForecastTool;
use App\AI\Tools\GetGoalsTool;
use App\AI\Tools\GetHealthScoreTool;
use App\AI\Tools\GetStatisticsTool;
use App\AI\Tools\GetSubscriptionsTool;
use App\Models\User;
use Carbon\Carbon;

class ReportWriterAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a professional financial report writer. Generate a comprehensive monthly financial report. Structure it with clear sections: 📋 Executive Summary, 💰 Income Analysis, 💸 Expense Analysis, 💵 Savings Performance, 🏥 Health Score, 🏆 Achievements, ⚠️ Areas for Improvement, 🎯 Next Month Plan. Use emojis and Telegram-friendly formatting. Be specific with numbers.";
    }

    protected function tools(): array
    {
        return [
            GetStatisticsTool::class,
            GetHealthScoreTool::class,
            GetForecastTool::class,
            GetGoalsTool::class,
            GetSubscriptionsTool::class,
        ];
    }

    public function monthlyReport(User $user, string $currency, Carbon $month): string
    {
        $start   = $month->copy()->startOfMonth();
        $end     = $month->copy()->endOfMonth();
        $context = $this->gatherContext($user, $this->tools(), [
            'currency' => $currency,
            'start'    => $start->toDateString(),
            'end'      => $end->toDateString(),
        ]);

        return $this->callLlm(
            $this->systemPrompt($user),
            "Generate the monthly financial report for {$month->format('F Y')}.",
            $context,
            2048
        );
    }
}
