<?php

namespace App\AI\Agents;

use App\AI\Tools\GetForecastTool;
use App\AI\Tools\GetGoalsTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;

class GoalPlannerAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are a goal planning advisor. For each active goal, explain current progress, provide a realistic completion timeline, and suggest the best strategy to achieve it faster. Be specific and encouraging. Format clearly for Telegram with emojis.";
    }

    protected function tools(): array
    {
        return [GetGoalsTool::class, GetForecastTool::class, GetStatisticsTool::class];
    }

    public function planGoals(User $user, string $currency): string
    {
        $context = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);

        return $this->callLlm(
            $this->systemPrompt($user),
            "Provide a goal plan with timelines and strategies for each active goal.",
            $context,
            1024
        );
    }
}
