<?php

namespace App\AI\Tools;

use App\Models\User;

class GetAccountsTool implements AiToolInterface
{
    public function execute(User $user, array $params = []): array
    {
        $accounts = $user->accounts()->where('is_archived', false)->get();

        return [
            'accounts' => $accounts->map(fn ($a) => [
                'name'     => $a->name,
                'type'     => $a->type,
                'currency' => $a->currency,
                'balance'  => (float) $a->current_balance,
            ])->values()->all(),
            'total_by_currency' => $accounts->groupBy('currency')
                ->map(fn ($g) => round((float) $g->sum('current_balance'), 2))
                ->all(),
        ];
    }
}
