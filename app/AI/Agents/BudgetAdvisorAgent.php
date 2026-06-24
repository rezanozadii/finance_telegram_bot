<?php

namespace App\AI\Agents;

use App\AI\Tools\GetBudgetsTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;

class BudgetAdvisorAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a budget advisor. Review budget status and provide specific recommendations. For exceeded budgets, explain consequences and suggest adjustments. For healthy budgets, acknowledge good management. Use emojis for Telegram formatting.";
    }

    protected function tools(): array
    {
        return [GetBudgetsTool::class, GetStatisticsTool::class];
    }

    public function advise(User $user): string
    {
        $currency = $user->default_currency ?? 'USD';
        $context  = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);

        return $this->callLlm(
            $user,
            "Review budgets and provide recommendations.",
            $context,
            1024
        );
    }
}
