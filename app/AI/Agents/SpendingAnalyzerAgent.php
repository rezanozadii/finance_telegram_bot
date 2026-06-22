<?php

namespace App\AI\Agents;

use App\AI\Tools\GetHabitsTool;
use App\AI\Tools\GetSpendingPatternsTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;
use Carbon\Carbon;

class SpendingAnalyzerAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a spending analyst. Analyze this user's spending patterns and habits. Identify the top 3 insights. Be data-driven, reference specific numbers and percentages. Format clearly for Telegram with emojis.";
    }

    protected function tools(): array
    {
        return [GetStatisticsTool::class, GetSpendingPatternsTool::class, GetHabitsTool::class];
    }

    public function analyze(User $user, string $currency, Carbon $start, Carbon $end): string
    {
        $context = $this->gatherContext($user, $this->tools(), [
            'currency' => $currency,
            'start'    => $start->toDateString(),
            'end'      => $end->toDateString(),
        ]);

        return $this->callLlm(
            $this->systemPrompt($user),
            "Analyze spending patterns and provide top 3 insights with concrete data.",
            $context,
            1024
        );
    }
}
