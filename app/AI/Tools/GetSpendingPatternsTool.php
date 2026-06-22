<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\SpendingPatternService;

class GetSpendingPatternsTool implements AiToolInterface
{
    public function __construct(private SpendingPatternService $patterns) {}

    public function execute(User $user, array $params = []): array
    {
        $currency = $params['currency'] ?? $user->default_currency ?? 'USD';
        return ['spending_patterns' => $this->patterns->detect($user, $currency)];
    }
}
