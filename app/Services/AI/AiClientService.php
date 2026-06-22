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

    private function stripThinkingTags(string $text): string
    {
        $cleaned = preg_replace('/<think>.*?<\/think>/s', '', $text);
        return trim($cleaned ?? $text);
    }
}
