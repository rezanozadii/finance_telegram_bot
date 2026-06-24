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
            $u = User::where('telegram_id', $telegramId)->first();
            $this->state->clear($telegramId);
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => $u?->language === 'fa' ? '✅ از حالت چت خارج شدید.' : '✅ Exited AI chat mode.',
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

        Telegram::sendChatAction(['chat_id' => $chatId, 'action' => 'typing']);

        // Send placeholder that we'll edit in-place as the response streams in
        $placeholder = $user->language === 'fa' ? '⌛ در حال پردازش...' : '⌛ Thinking...';
        $sent        = Telegram::sendMessage(['chat_id' => $chatId, 'text' => $placeholder]);
        $messageId   = $sent->getMessageId();

        try {
            $currency    = $user->default_currency ?? 'USD';
            $accumulated = '';
            $lastEditAt  = 0.0;

            foreach ($this->orchestrator->handleStream($user, $text, $currency) as $chunk) {
                $accumulated .= $chunk;
                $now = microtime(true);

                // Edit the message every ~0.8 seconds to show progressive output
                if (($now - $lastEditAt) >= 0.8 && trim($accumulated) !== '') {
                    try {
                        Telegram::editMessageText([
                            'chat_id'    => $chatId,
                            'message_id' => $messageId,
                            'text'       => $accumulated . ' ▌',
                        ]);
                        $lastEditAt = $now;
                    } catch (\Throwable) {
                        // Ignore edit errors (e.g. message unchanged, flood limit)
                    }
                }
            }

            // Final edit — apply parse_mode on the complete response
            if (trim($accumulated) !== '') {
                Telegram::editMessageText([
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => $accumulated,
                    'parse_mode' => 'Markdown',
                ]);
            }
        } catch (\Throwable) {
            $errText = $user->language === 'fa'
                ? '⚠️ پردازش درخواست امکان‌پذیر نیست. دوباره تلاش کنید.'
                : '⚠️ Unable to process your request. Please try again.';
            try {
                Telegram::editMessageText([
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => $errText,
                ]);
            } catch (\Throwable) {
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => $errText]);
            }
        }
    }
}
