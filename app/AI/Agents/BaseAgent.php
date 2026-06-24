<?php

namespace App\AI\Agents;

use App\AI\Tools\AiToolInterface;
use App\Models\User;
use App\Services\AI\AiClientService;

abstract class BaseAgent
{
    public function __construct(protected AiClientService $client) {}

    abstract protected function systemPrompt(User $user): string;

    abstract protected function tools(): array;

    protected function gatherContext(User $user, array $toolNames, array $params = []): array
    {
        $context = [];

        foreach ($toolNames as $toolClass) {
            try {
                /** @var AiToolInterface $tool */
                $tool    = app($toolClass);
                $result  = $tool->execute($user, $params);
                $context = array_merge($context, $result);
            } catch (\Throwable) {
            }
        }

        return $context;
    }

    protected function callLlm(string $system, string $userMessage, array $context, int $maxTokens = 1024): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $fullMessage = "Financial Data:\n```json\n{$contextJson}\n```\n\n{$userMessage}";

        return $this->client->complete($system, $fullMessage, $maxTokens);
    }

    protected function callLlmStream(string $system, string $userMessage, array $context, int $maxTokens = 1024): \Generator
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $fullMessage = "Financial Data:\n```json\n{$contextJson}\n```\n\n{$userMessage}";

        yield from $this->client->completeStream($system, $fullMessage, $maxTokens);
    }
}
