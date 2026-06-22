<?php

namespace App\AI\Tools;

use App\Models\User;

interface AiToolInterface
{
    public function execute(User $user, array $params = []): array;
}
