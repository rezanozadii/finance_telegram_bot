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

    /**
     * Appends a hard language instruction so the LLM always responds in
     * the user's chosen language, regardless of the data language.
     */
    private function systemPromptWithLang(User $user): string
    {
        $base = $this->systemPrompt($user);
        $lang = $user->language === 'fa'
            ? ' IMPORTANT: You MUST respond entirely in Persian (Farsi). Do not write any English words or sentences.'
            : ' Respond in English.';
        return $base . $lang;
    }

    /**
     * Call the LLM with language enforcement baked in.
     * Pass the User object so the system prompt is automatically localised.
     */
    protected function callLlm(User $user, string $userMessage, array $context, int $maxTokens = 1024): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $fullMessage = "Financial Data:\n```json\n{$contextJson}\n```\n\n{$userMessage}";

        return $this->client->complete($this->systemPromptWithLang($user), $fullMessage, $maxTokens);
    }

    /**
     * Streaming variant — yields tokens as they arrive from the LLM.
     */
    protected function callLlmStream(User $user, string $userMessage, array $context, int $maxTokens = 1024): \Generator
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $fullMessage = "Financial Data:\n```json\n{$contextJson}\n```\n\n{$userMessage}";

        yield from $this->client->completeStream($this->systemPromptWithLang($user), $fullMessage, $maxTokens);
    }
}
