<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AccountService
{
    public function create(User $user, string $name, string $type, string $currency, float $initialBalance): Account
    {
        return Account::create([
            'user_id'         => $user->id,
            'name'            => $name,
            'type'            => $type,
            'currency'        => strtoupper($currency),
            'current_balance' => $initialBalance,
            'is_archived'     => false,
        ]);
    }

    public function listActive(User $user): Collection
    {
        return $user->accounts()->where('is_archived', false)->orderBy('name')->get();
    }

    public function archive(Account $account): void
    {
        $account->update(['is_archived' => true]);
    }

    public function rename(Account $account, string $name): void
    {
        $account->update(['name' => $name]);
    }

    /** Adjust balance by a signed delta (positive = credit, negative = debit). */
    public function adjustBalance(Account $account, float $delta): void
    {
        $account->increment('current_balance', $delta);
    }

    public function totalBalance(User $user): float
    {
        return (float) $user->accounts()
            ->where('is_archived', false)
            ->sum('current_balance');
    }
}
