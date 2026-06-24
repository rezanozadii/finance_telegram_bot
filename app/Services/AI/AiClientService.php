<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use OpenAI;

class AiClientService
{
    public function complete(string $system, string $user, int $maxTokens = 1024): string
    {
        if (!config('services.deepseek.api_key')) {
            return 'AI service not configured.';
        }

        try {
            $client = OpenAI::factory()
                ->withApiKey(config('services.deepseek.api_key'))
                ->withBaseUri(config('services.deepseek.base_url'))
                ->make();

            $response = $client->chat()->create([
                'model'      => config('services.deepseek.model'),
                'messages'   => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'max_tokens' => $maxTokens,
            ]);

            $raw = $response->choices[0]->message->content ?? '';

            return $this->stripThinkingTags($raw);

        } catch (\Throwable $e) {
            Log::error('AiClientService error', ['error' => $e->getMessage()]);
            return 'Unable to generate AI response at this time.';
        }
    }

    /**
     * Stream tokens from the LLM as a Generator.
     * Each yield is a raw delta string; caller accumulates them.
     */
    public function completeStream(string $system, string $user, int $maxTokens = 1024): \Generator
    {
        if (!config('services.deepseek.api_key')) {
            yield 'AI service not configured.';
            return;
        }

        try {
            $client = OpenAI::factory()
                ->withApiKey(config('services.deepseek.api_key'))
                ->withBaseUri(config('services.deepseek.base_url'))
                ->make();

            $stream = $client->chat()->createStreamed([
                'model'      => config('services.deepseek.model'),
                'messages'   => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'max_tokens' => $maxTokens,
            ]);

            $inThinkTag = false;
            foreach ($stream as $response) {
                $delta = $response->choices[0]->delta->content ?? '';
                if ($delta === '') {
                    continue;
                }
                // Strip <think>…</think> blocks on-the-fly
                if (str_contains($delta, '<think>')) {
                    $inThinkTag = true;
                }
                if (!$inThinkTag) {
                    yield $delta;
                }
                if ($inThinkTag && str_contains($delta, '</think>')) {
                    $inThinkTag = false;
                }
            }
        } catch (\Throwable $e) {
            Log::error('AiClientService stream error', ['error' => $e->getMessage()]);
            yield 'Unable to generate AI response at this time.';
        }
    }

    private function stripThinkingTags(string $text): string
    {
        $cleaned = preg_replace('/<think>.*?<\/think>/s', '', $text);
        return trim($cleaned ?? $text);
    }
}
