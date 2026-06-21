<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Services\AccountService;
use App\Telegram\Handlers\AccountHandler;
use App\Telegram\Keyboards\AccountKeyboard;
use Telegram\Bot\Commands\Command;

class AccountsCommand extends Command
{
    protected string $name = 'accounts';
    protected string $description = 'View and manage your accounts';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $accounts = app(AccountService::class)->listActive($user);

        if ($accounts->isEmpty()) {
            $this->replyWithMessage([
                'text'         => "You have no active accounts yet.",
                'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '➕ Add Account', 'callback_data' => 'account:add']]]]),
            ]);
            return;
        }

        $handler = app(AccountHandler::class);

        $this->replyWithMessage([
            'text'         => $handler->formatAccountList($accounts),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(AccountKeyboard::accountList($accounts)),
        ]);
    }
}
