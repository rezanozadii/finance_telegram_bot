<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ConversationStateService
{
    private const TTL = 600; // 10 minutes

    private function key(int|string $telegramId): string
    {
        return "conv_state:{$telegramId}";
    }

    public function get(int|string $telegramId): ?array
    {
        return Cache::get($this->key($telegramId));
    }

    public function set(int|string $telegramId, string $step, array $data = []): void
    {
        Cache::put($this->key($telegramId), ['step' => $step, 'data' => $data], self::TTL);
    }

    public function update(int|string $telegramId, array $data): void
    {
        $state = $this->get($telegramId) ?? ['step' => '', 'data' => []];
        $state['data'] = array_merge($state['data'], $data);
        Cache::put($this->key($telegramId), $state, self::TTL);
    }

    public function clear(int|string $telegramId): void
    {
        Cache::forget($this->key($telegramId));
    }

    public function step(int|string $telegramId): ?string
    {
        return $this->get($telegramId)['step'] ?? null;
    }

    public function data(int|string $telegramId): array
    {
        return $this->get($telegramId)['data'] ?? [];
    }
}
