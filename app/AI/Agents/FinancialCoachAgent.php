<?php

namespace App\AI\Agents;

use App\AI\Tools\GetBudgetsTool;
use App\AI\Tools\GetGoalsTool;
use App\AI\Tools\GetSpendingPatternsTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;

class FinancialCoachAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a personal financial coach. Based on this week's financial data, provide 3-5 actionable coaching points. Be specific, reference actual numbers, be encouraging but honest. Format with emojis for Telegram. Keep it concise and motivating. Respond in the user's language if indicated.";
    }

    protected function tools(): array
    {
        return [GetStatisticsTool::class, GetBudgetsTool::class, GetGoalsTool::class, GetSpendingPatternsTool::class];
    }

    public function weeklyCoaching(User $user, string $currency): string
    {
        $context = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);
        return $this->callLlm(
            $this->systemPrompt($user),
            "Generate weekly financial coaching for this user.",
            $context,
            1024
        );
    }
}
