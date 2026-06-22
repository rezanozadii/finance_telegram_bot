<?php

namespace App\AI\Tools;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class GetTransactionsTool implements AiToolInterface
{
    public function execute(User $user, array $params = []): array
    {
        $limit    = $params['limit'] ?? 20;
        $currency = $params['currency'] ?? $user->default_currency ?? 'USD';
        $start    = isset($params['start']) ? Carbon::parse($params['start']) : Carbon::now()->startOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('occurred_at', '>=', $start)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->with('category')
            ->get();

        return [
            'transactions' => $transactions->map(fn ($t) => [
                'type'        => $t->type,
                'amount'      => (float) $t->amount,
                'currency'    => $t->currency,
                'merchant'    => $t->merchant,
                'category'    => $t->category?->name,
                'description' => $t->description,
                'date'        => Carbon::parse($t->occurred_at)->toDateString(),
            ])->values()->all(),
            'count'    => $transactions->count(),
            'currency' => $currency,
        ];
    }
}
