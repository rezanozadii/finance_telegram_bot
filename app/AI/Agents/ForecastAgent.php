<?php

namespace App\AI\Agents;

use App\AI\Tools\GetForecastTool;
use App\AI\Tools\GetGoalsTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;

class ForecastAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a financial forecaster. Based on this data, explain the financial outlook in plain, friendly language. Include end-of-month projection, goal progress, and risk warnings. Use emojis for Telegram. Be honest about risks.";
    }

    protected function tools(): array
    {
        return [GetForecastTool::class, GetGoalsTool::class, GetStatisticsTool::class];
    }

    public function forecast(User $user, string $currency): string
    {
        $context = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);

        return $this->callLlm(
            $user,
            "Explain the financial forecast and outlook.",
            $context,
            1024
        );
    }
}
