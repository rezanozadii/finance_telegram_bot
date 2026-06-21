<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Services\CategoryService;
use App\Telegram\Handlers\CategoryHandler;
use Telegram\Bot\Commands\Command;

class CategoriesCommand extends Command
{
    protected string $name = 'categories';
    protected string $description = 'View and manage your categories';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first to register.']);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();

        app(CategoryHandler::class)->showList($from->getId(), $chatId);
    }
}
