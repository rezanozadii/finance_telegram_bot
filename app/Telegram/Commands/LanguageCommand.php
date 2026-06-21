<?php

namespace App\Telegram\Commands;

use App\Models\User;
use Telegram\Bot\Commands\Command;

class LanguageCommand extends Command
{
    protected string $name        = 'language';
    protected string $description = 'Change bot language / تغییر زبان ربات';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => 'Please send /start first. / ابتدا /start را ارسال کنید.']);
            return;
        }

        $this->replyWithMessage([
            'text'         => __('bot.choose_language'),
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '🇬🇧 English', 'callback_data' => 'lang:en'],
                        ['text' => '🇮🇷 فارسی',   'callback_data' => 'lang:fa'],
                    ],
                ],
            ]),
        ]);
    }
}
