<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\FriendHandler;
use Telegram\Bot\Commands\Command;

class AddFriendCommand extends Command
{
    protected string $name = 'addfriend';
    protected string $description = 'Add a friend by Telegram username';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first to register.']);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();

        // Parse username from command text: "/addfriend @alice" or "/addfriend alice"
        $text     = $this->getUpdate()->getMessage()->getText() ?? '';
        $parts    = explode(' ', trim($text), 2);
        $username = trim($parts[1] ?? '');

        app(FriendHandler::class)->handleAddFriendCommand($from->getId(), $chatId, $username);
    }
}
