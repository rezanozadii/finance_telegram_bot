<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Services\TransactionService;
use Telegram\Bot\Commands\Command;

class TransactionsCommand extends Command
{
    protected string $name = 'transactions';
    protected string $description = 'View recent transactions';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first to register.']);
            return;
        }

        $transactions = app(TransactionService::class)->listRecent($user, 10);

        if ($transactions->isEmpty()) {
            $this->replyWithMessage(['text' => 'No transactions yet. Use /add to log one.']);
            return;
        }

        $lines = ["📋 *Recent Transactions*\n"];

        foreach ($transactions as $txn) {
            $typeEmoji = match ($txn->type) {
                'income'   => '💰',
                'expense'  => '💸',
                'transfer' => '🔄',
            };

            $amount = number_format($txn->amount, 2);
            $date   = $txn->occurred_at->format('M d');
            $label  = $txn->description
                ? $txn->description
                : ($txn->category?->name ?? ($txn->type === 'transfer' ? "→ {$txn->toAccount?->name}" : 'Uncategorized'));

            $lines[] = "{$typeEmoji} {$date} · *{$txn->currency} {$amount}* · {$label} _({$txn->account->name})_";
        }

        $this->replyWithMessage([
            'text'       => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ]);
    }
}
