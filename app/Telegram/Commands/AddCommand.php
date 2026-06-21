<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\TransactionHandler;
use Telegram\Bot\Commands\Command;

class AddCommand extends Command
{
    protected string $name = 'add';
    protected string $description = 'Log a new transaction manually';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first to register.']);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        app(TransactionHandler::class)->startManualEntry($from->getId(), $chatId);
    }
}
