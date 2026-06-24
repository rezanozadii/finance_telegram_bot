<?php

namespace App\AI\Agents;

use App\AI\Tools\GetSubscriptionsTool;
use App\Models\User;

class SubscriptionAnalyzerAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a subscription analyst. Review detected subscriptions. Identify total monthly and yearly costs. Flag any that seem expensive or potentially underused. Suggest which subscriptions to keep, cancel, or review. Format clearly for Telegram with emojis.";
    }

    protected function tools(): array
    {
        return [GetSubscriptionsTool::class];
    }

    public function analyze(User $user): string
    {
        $context = $this->gatherContext($user, $this->tools());

        return $this->callLlm(
            $user,
            "Review subscriptions and identify cost-saving opportunities.",
            $context,
            1024
        );
    }
}
