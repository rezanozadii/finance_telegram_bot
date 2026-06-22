<?php

namespace App\Telegram\Handlers;

use App\AI\AgentOrchestrator;
use App\Models\User;
use App\Services\ConversationStateService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Message;

class AiChatHandler
{
    public function __construct(
        private AgentOrchestrator $orchestrator,
        private ConversationStateService $state,
    ) {}

    public function handle(Message $message): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        if (in_array(strtolower($text), ['/done', '/exit', '/stop'])) {
            $this->state->clear($telegramId);
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => '✅ Exited AI chat mode.',
            ]);
            return;
        }

        if (empty($text)) {
            return;
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return;
        }

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user->language === 'fa' ? '⏳ در حال پردازش...' : '⏳ Thinking...',
        ]);

        try {
            $currency = $user->default_currency ?? 'USD';
            $response = $this->orchestrator->handle($user, $text, $currency);

            Telegram::sendMessage([
                'chat_id'    => $chatId,
                'text'       => $response,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => '⚠️ Unable to process your request. Please try again.',
            ]);
        }
    }
}
