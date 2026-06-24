<?php

namespace App\AI\Agents;

use App\AI\Tools\GetBudgetsTool;
use App\AI\Tools\GetHealthScoreTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;

class FinancialHealthAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a financial health advisor. Explain the user's financial health score component by component. For each component, explain what it means, why the score is what it is, and how to improve it. Prioritize the top 3 improvement opportunities. Use emojis and clear formatting for Telegram.";
    }

    protected function tools(): array
    {
        return [GetHealthScoreTool::class, GetStatisticsTool::class, GetBudgetsTool::class];
    }

    public function explainScore(User $user, string $currency): string
    {
        $context = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);

        return $this->callLlm(
            $user,
            "Explain the financial health score and provide improvement recommendations.",
            $context,
            1024
        );
    }
}
