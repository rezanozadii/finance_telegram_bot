<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Services\TransactionService;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class TransactionsCommand extends Command
{
    protected string $name = 'transactions';
    protected string $description = 'View recent transactions';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $lang         = $user->language ?? 'en';
        $transactions = app(TransactionService::class)->listRecent($user, 10);
        $chatId       = $this->getUpdate()->getMessage()->getChat()->getId();

        if ($transactions->isEmpty()) {
            Telegram::sendMessage([
                'chat_id'      => $chatId,
                'text'         => __('bot.txn_none'),
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => $lang === 'fa' ? '➕ افزودن تراکنش' : '➕ Add Transaction', 'callback_data' => 'txn:start'],
                    ]],
                ]),
            ]);
            return;
        }

        $lines = [$lang === 'fa' ? "📋 *تراکنش‌های اخیر*\n" : "📋 *Recent Transactions*\n"];

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

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => implode("\n", $lines),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $lang === 'fa' ? '💸 هزینه' : '💸 Expense',   'callback_data' => 'txn_filter:expense'],
                        ['text' => $lang === 'fa' ? '💰 درآمد' : '💰 Income',    'callback_data' => 'txn_filter:income'],
                        ['text' => $lang === 'fa' ? '🔄 انتقال' : '🔄 Transfer', 'callback_data' => 'txn_filter:transfer'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '➕ افزودن تراکنش' : '➕ Add Transaction', 'callback_data' => 'txn:start'],
                    ],
                ],
            ]),
        ]);
    }
}
