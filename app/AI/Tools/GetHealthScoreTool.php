<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\HealthScoreService;

class GetHealthScoreTool implements AiToolInterface
{
    public function __construct(private HealthScoreService $healthScore) {}

    public function execute(User $user, array $params = []): array
    {
        $currency = $params['currency'] ?? $user->default_currency ?? 'USD';
        return $this->healthScore->calculate($user, $currency);
    }
}
