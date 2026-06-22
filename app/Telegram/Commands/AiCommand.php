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

        app(ConversationStateService::class)->set($from->getId(), 'ai_chat');

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $user->language === 'fa'
                ? "🤖 *دستیار مالی هوش مصنوعی*\n\nسلام! من دستیار مالی شما هستم. هر سوالی درباره مالیه‌هاتون دارید بپرسید.\n\nبرای خروج /done را بزنید."
                : "🤖 *AI Financial Assistant*\n\nHello! I'm your personal finance AI. Ask me anything about your finances.\n\nType /done to exit.",
            'parse_mode'   => 'Markdown',
        ]);
    }
}
