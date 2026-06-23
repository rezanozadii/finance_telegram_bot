<?php

namespace App\Telegram\Commands;

use App\Services\AccountService;
use App\Services\UserService;
use App\Telegram\Handlers\AccountHandler;
use App\Telegram\Keyboards\MainKeyboard;
use Illuminate\Support\Facades\App;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Register and get started';

    public function handle(): void
    {
        $from     = $this->getUpdate()->getMessage()->getFrom();
        $chatId   = $this->getUpdate()->getMessage()->getChat()->getId();
        $user     = app(UserService::class)->findOrCreate($from);
        $accounts = app(AccountService::class)->listActive($user);
        $lang     = $user->language ?? 'en';

        App::setLocale($lang);

        if ($accounts->isEmpty()) {
            $this->replyWithMessage([
                'text' => __('bot.welcome_new', ['name' => $user->display_name]),
            ]);

            app(AccountHandler::class)->startCreation($from->getId(), $chatId);
        } else {
            $this->replyWithMessage([
                'text'         => __('bot.welcome_back', ['name' => $user->display_name, 'count' => $accounts->count()]),
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode(MainKeyboard::main($lang)),
            ]);
        }
    }
}
