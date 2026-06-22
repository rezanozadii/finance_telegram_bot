<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\ForecastingService;

class GetForecastTool implements AiToolInterface
{
    public function __construct(private ForecastingService $forecasting) {}

    public function execute(User $user, array $params = []): array
    {
        $currency = $params['currency'] ?? $user->default_currency ?? 'USD';
        return $this->forecasting->forecast($user, $currency);
    }
}
