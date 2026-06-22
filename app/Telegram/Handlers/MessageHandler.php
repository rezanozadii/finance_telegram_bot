<?php

namespace App\Telegram\Handlers;

use App\Services\ConversationStateService;
use Telegram\Bot\Objects\Message;

class MessageHandler
{
    public function __construct(
        private ConversationStateService $state,
        private AccountHandler $accountHandler,
        private CategoryHandler $categoryHandler,
        private TransactionHandler $transactionHandler,
        private AiTransactionHandler $aiTransactionHandler,
        private RecurringHandler $recurringHandler,
        private FriendHandler $friendHandler,
        private AiChatHandler $aiChatHandler,
        private GoalHandler $goalHandler,
        private BudgetHandler $budgetHandler,
    ) {}

    public function handle(Message $message): void
    {
        $telegramId = $message->getFrom()?->getId();
        if (!$telegramId) {
            return;
        }

        $step = $this->state->step($telegramId);

        if ($step === null) {
            $this->aiTransactionHandler->handle($message);
            return;
        }

        match (true) {
            $step === 'ai_chat'                  => $this->aiChatHandler->handle($message),
            str_starts_with($step, 'goal.')      => $this->goalHandler->handleMessage($message, $step),
            str_starts_with($step, 'budget.')    => $this->budgetHandler->handleMessage($message, $step),
            str_starts_with($step, 'account.')   => $this->accountHandler->handleMessage($message, $step),
            str_starts_with($step, 'category.')  => $this->categoryHandler->handleMessage($message, $step),
            str_starts_with($step, 'txn.')       => $this->transactionHandler->handleMessage($message, $step),
            str_starts_with($step, 'recurring.') => $this->recurringHandler->handleMessage($message, $step),
            str_starts_with($step, 'friend.')    => $this->friendHandler->handleMessage($message, $step),
            default => null,
        };
    }
}
