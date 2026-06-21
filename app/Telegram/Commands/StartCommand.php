<?php

namespace App\Telegram\Commands;

use App\Services\AccountService;
use App\Services\UserService;
use App\Telegram\Handlers\AccountHandler;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Register and get started';

    public function handle(): void
    {
        $from    = $this->getUpdate()->getMessage()->getFrom();
        $chatId  = $this->getUpdate()->getMessage()->getChat()->getId();
        $user    = app(UserService::class)->findOrCreate($from);
        $accounts = app(AccountService::class)->listActive($user);

        if ($accounts->isEmpty()) {
            $this->replyWithMessage([
                'text' => "👋 Welcome, {$user->display_name}!\n\nI'm your personal finance tracker. Let's start by creating your first account.",
            ]);

            app(AccountHandler::class)->startCreation($from->getId(), $chatId);
        } else {
            $this->replyWithMessage([
                'text' => "👋 Welcome back, {$user->display_name}!\n\nYou have {$accounts->count()} account(s). Use /accounts to manage them or just send me a transaction like:\n\n_\"25 lunch cash\"_",
                'parse_mode' => 'Markdown',
            ]);
        }
    }
}
