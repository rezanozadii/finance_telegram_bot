<?php

namespace App\AI\Agents;

use App\AI\Tools\GetAccountsTool;
use App\AI\Tools\GetBudgetsTool;
use App\AI\Tools\GetForecastTool;
use App\AI\Tools\GetGoalsTool;
use App\AI\Tools\GetStatisticsTool;
use App\Models\User;

class ChatAgent extends BaseAgent
{
    protected function systemPrompt(User $user): string
    {
        return "You are an AI personal finance assistant. Answer the user's financial question using the provided data. Be concise, friendly, and always reference actual numbers from their data. If asked about something not in the data, say so honestly. Use emojis for Telegram. Keep answers focused and actionable.";
    }

    protected function tools(): array
    {
        return [
            GetStatisticsTool::class,
            GetAccountsTool::class,
            GetGoalsTool::class,
            GetBudgetsTool::class,
            GetForecastTool::class,
        ];
    }

    public function chat(User $user, string $question, string $currency): string
    {
        $context = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);

        return $this->callLlm(
            $this->systemPrompt($user),
            $question,
            $context,
            1024
        );
    }

    public function chatStream(User $user, string $question, string $currency): \Generator
    {
        $context = $this->gatherContext($user, $this->tools(), ['currency' => $currency]);

        yield from $this->callLlmStream(
            $this->systemPrompt($user),
            $question,
            $context,
            1024
        );
    }
}
