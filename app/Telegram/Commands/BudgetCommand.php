<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\BudgetHandler;
use Telegram\Bot\Commands\Command;

class BudgetCommand extends Command
{
    protected string $name        = 'budget';
    protected string $description = 'Manage your budgets';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        app(BudgetHandler::class)->showList($from->getId(), $chatId);
    }
}
