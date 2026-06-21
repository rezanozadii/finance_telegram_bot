<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\RecurringHandler;
use Telegram\Bot\Commands\Command;

class RecurringCommand extends Command
{
    protected string $name = 'recurring';
    protected string $description = 'Manage recurring transactions';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first to register.']);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        app(RecurringHandler::class)->showList($from->getId(), $chatId);
    }
}
