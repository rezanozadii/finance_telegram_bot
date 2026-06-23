<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Services\ConversationStateService;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class AiCommand extends Command
{
    protected string $name        = 'ai';
    protected string $description = 'Start AI financial assistant chat';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        $lang   = $user->language ?? 'en';

        app(ConversationStateService::class)->set($from->getId(), 'ai_chat');

        $text = $lang === 'fa'
            ? "🤖 *دستیار مالی هوش مصنوعی*\n\nسلام! سوال مالی خود را بنویسید یا یک گزینه سریع انتخاب کنید.\n\nبرای خروج /done را بزنید."
            : "🤖 *AI Financial Assistant*\n\nHello! Ask me anything about your finances, or pick a quick option below.\n\nType /done to exit.";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $lang === 'fa' ? '❤️ سلامت مالی' : '❤️ Health Score',   'callback_data' => 'settings:health'],
                        ['text' => $lang === 'fa' ? '💡 بینش‌ها' : '💡 Daily Insights',    'callback_data' => 'settings:insights'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '🏋️ مشاوره مالی' : '🏋️ Financial Coach', 'callback_data' => 'settings:coach'],
                        ['text' => $lang === 'fa' ? '🔄 اشتراک‌ها' : '🔄 Subscriptions',    'callback_data' => 'ai:subscriptions'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '❌ خروج از چت' : '❌ Exit chat', 'callback_data' => 'ai:exit'],
                    ],
                ],
            ]),
        ]);
    }
}
