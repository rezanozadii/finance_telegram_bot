<?php

namespace App\AI\Tools;

use App\Models\User;
use App\Services\AI\SubscriptionDetectorService;

class GetSubscriptionsTool implements AiToolInterface
{
    public function __construct(private SubscriptionDetectorService $detector) {}

    public function execute(User $user, array $params = []): array
    {
        return ['subscriptions' => $this->detector->detect($user)];
    }
}
