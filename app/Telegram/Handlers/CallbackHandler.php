<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;

class CallbackHandler
{
    public function __construct(
        private AccountHandler $accountHandler,
        private CategoryHandler $categoryHandler,
        private TransactionHandler $transactionHandler,
        private AiTransactionHandler $aiTransactionHandler,
        private RecurringHandler $recurringHandler,
        private FriendHandler $friendHandler,
        private ReportHandler $reportHandler,
    ) {}

    private function handleLanguage(CallbackQuery $query, string $action): void
    {
        $lang       = substr($action, 5); // strip 'lang:'
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        if (!in_array($lang, ['en', 'fa'])) {
            return;
        }

        User::where('telegram_id', $telegramId)->update(['language' => $lang]);
        App::setLocale($lang);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.language_set'),
        ]);
    }

    public function handle(CallbackQuery $query): void
    {
        $action = $query->getData() ?? '';

        match (true) {
            str_starts_with($action, 'account')  => $this->accountHandler->handleCallback($query, $action),
            str_starts_with($action, 'category') => $this->categoryHandler->handleCallback($query, $action),
            str_starts_with($action, 'ai_txn')   => $this->aiTransactionHandler->handleCallback($query, $action),
            str_starts_with($action, 'txn')      => $this->transactionHandler->handleCallback($query, $action),
            str_starts_with($action, 'rec')      => $this->recurringHandler->handleCallback($query, $action),
            str_starts_with($action, 'friend')   => $this->friendHandler->handleCallback($query, $action),
            str_starts_with($action, 'report:')  => $this->reportHandler->handleCallback($query, $action),
            str_starts_with($action, 'lang:')    => $this->handleLanguage($query, $action),
            default => null,
        };
    }
}
