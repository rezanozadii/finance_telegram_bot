<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(private AccountService $accountService) {}

    /**
     * Single entry point for both manual and AI-parsed transactions.
     * $data keys: type, account_id, category_id, amount, currency,
     *             merchant, description, occurred_at, to_account_id,
     *             source, raw_input_text
     */
    public function createTransaction(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $account  = Account::findOrFail($data['account_id']);
            $currency = $data['currency'] ?? $account->currency;

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'account_id'     => $data['account_id'],
                'category_id'    => $data['category_id'] ?? null,
                'type'           => $data['type'],
                'amount'         => $data['amount'],
                'currency'       => $currency,
                'merchant'       => $data['merchant'] ?? null,
                'description'    => $data['description'] ?? null,
                'occurred_at'    => $data['occurred_at'] ?? now(),
                'to_account_id'  => $data['to_account_id'] ?? null,
                'source'         => $data['source'] ?? 'manual',
                'raw_input_text' => $data['raw_input_text'] ?? null,
            ]);

            $this->applyBalanceChanges($transaction);

            return $transaction;
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->reverseBalanceChanges($transaction);
            $transaction->delete();
        });
    }

    public function listRecent(User $user, int $limit = 10): Collection
    {
        return $user->transactions()
            ->with(['account', 'category', 'toAccount'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    // ── Balance helpers ──────────────────────────────────────────────────────

    private function applyBalanceChanges(Transaction $transaction): void
    {
        $account = Account::find($transaction->account_id);

        match ($transaction->type) {
            'income'   => $this->accountService->adjustBalance($account, (float) $transaction->amount),
            'expense'  => $this->accountService->adjustBalance($account, -(float) $transaction->amount),
            'transfer' => $this->applyTransfer($transaction, $account),
        };
    }

    private function reverseBalanceChanges(Transaction $transaction): void
    {
        $account = Account::find($transaction->account_id);

        match ($transaction->type) {
            'income'   => $this->accountService->adjustBalance($account, -(float) $transaction->amount),
            'expense'  => $this->accountService->adjustBalance($account, (float) $transaction->amount),
            'transfer' => $this->reverseTransfer($transaction, $account),
        };
    }

    private function applyTransfer(Transaction $transaction, Account $from): void
    {
        $to = Account::find($transaction->to_account_id);
        $this->accountService->adjustBalance($from, -(float) $transaction->amount);
        $this->accountService->adjustBalance($to, (float) $transaction->amount);
    }

    private function reverseTransfer(Transaction $transaction, Account $from): void
    {
        $to = Account::find($transaction->to_account_id);
        $this->accountService->adjustBalance($from, (float) $transaction->amount);
        $this->accountService->adjustBalance($to, -(float) $transaction->amount);
    }
}
