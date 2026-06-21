<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\FriendHandler;
use Telegram\Bot\Commands\Command;

class FriendsCommand extends Command
{
    protected string $name = 'friends';
    protected string $description = 'Manage friends and shared expenses';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first to register.']);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        app(FriendHandler::class)->showList($from->getId(), $chatId);
    }
}
