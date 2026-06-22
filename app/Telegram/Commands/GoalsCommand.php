<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\GoalHandler;
use Telegram\Bot\Commands\Command;

class GoalsCommand extends Command
{
    protected string $name        = 'goals';
    protected string $description = 'Manage your financial goals';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        app(GoalHandler::class)->showList($from->getId(), $chatId);
    }
}
