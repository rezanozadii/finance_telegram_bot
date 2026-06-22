<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\HabitDetectorService;

class GetHabitsTool implements AiToolInterface
{
    public function __construct(private HabitDetectorService $habitDetector) {}

    public function execute(User $user, array $params = []): array
    {
        $currency = $params['currency'] ?? $user->default_currency ?? 'USD';
        return ['habits' => $this->habitDetector->detect($user, $currency)];
    }
}
