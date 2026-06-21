<?php

namespace App\Telegram\Handlers;

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
            default => null,
        };
    }
}
