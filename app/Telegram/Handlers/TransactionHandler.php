<?php

namespace App\Telegram\Handlers;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Services\ConversationStateService;
use App\Services\TransactionService;
use App\Telegram\Keyboards\TransactionKeyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class TransactionHandler
{
    public function __construct(
        private TransactionService $transactionService,
        private AccountService $accountService,
        private CategoryService $categoryService,
        private ConversationStateService $state,
    ) {}

    // ── Message (text input) steps ──────────────────────────────────────────

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'txn.amount' => $this->stepAmount($telegramId, $chatId, $text),
            'txn.note'   => $this->stepNote($telegramId, $chatId, $text),
            default      => null,
        };
    }

    // ── Callback query steps ────────────────────────────────────────────────

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            str_starts_with($action, 'txn_type:')       => $this->stepType($telegramId, $chatId, $messageId, substr($action, 9)),
            str_starts_with($action, 'txn_account:')    => $this->stepAccount($telegramId, $chatId, $messageId, (int) substr($action, 12)),
            str_starts_with($action, 'txn_to_account:') => $this->stepToAccount($telegramId, $chatId, $messageId, (int) substr($action, 15)),
            str_starts_with($action, 'txn_category:')   => $this->stepCategory($telegramId, $chatId, $messageId, (int) substr($action, 13)),
            $action === 'txn_note:skip'                  => $this->stepNoteSkip($telegramId, $chatId, $messageId),
            $action === 'txn:confirm'                    => $this->doConfirm($telegramId, $chatId, $messageId),
            $action === 'txn:cancel'                     => $this->doCancel($telegramId, $chatId, $messageId),
            default => null,
        };
    }

    // ── Start flow ──────────────────────────────────────────────────────────

    public function startManualEntry(int|string $telegramId, int|string $chatId): void
    {
        $user     = User::where('telegram_id', $telegramId)->first();
        $accounts = $user ? $this->accountService->listActive($user) : collect();

        if ($accounts->isEmpty()) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => "You have no accounts yet. Use /accounts to create one first.",
            ]);
            return;
        }

        $this->state->set($telegramId, 'txn.type');

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => "New transaction. What type?",
            'reply_markup' => json_encode(TransactionKeyboard::typeSelector()),
        ]);
    }

    // ── Conversation steps ──────────────────────────────────────────────────

    private function stepType(int|string $telegramId, int|string $chatId, int $messageId, string $type): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $accounts = $this->accountService->listActive($user);

        $this->state->set($telegramId, 'txn.account', ['type' => $type]);

        $prompt = $type === 'transfer' ? "Transfer *from* which account?" : "Which account?";

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $prompt,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(TransactionKeyboard::accountSelector($accounts)),
        ]);
    }

    private function stepAccount(int|string $telegramId, int|string $chatId, int $messageId, int $accountId): void
    {
        $data    = $this->state->data($telegramId);
        $account = $this->ownedAccount($telegramId, $accountId);

        if (!$account) {
            return;
        }

        $data['account_id']   = $accountId;
        $data['account_name'] = $account->name;
        $data['currency']     = $account->currency;

        if ($data['type'] === 'transfer') {
            $user         = User::where('telegram_id', $telegramId)->firstOrFail();
            $otherAccounts = $this->accountService->listActive($user)->where('id', '!=', $accountId);

            if ($otherAccounts->isEmpty()) {
                Telegram::editMessageText([
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => "You need at least 2 accounts to make a transfer.",
                ]);
                $this->state->clear($telegramId);
                return;
            }

            $this->state->set($telegramId, 'txn.to_account', $data);

            Telegram::editMessageText([
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'text'         => "Transfer *to* which account?",
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode(TransactionKeyboard::accountSelector($otherAccounts, 'txn_to_account')),
            ]);
            return;
        }

        // Income or expense — pick category
        $user       = User::where('telegram_id', $telegramId)->firstOrFail();
        $categories = $this->categoryService->topLevel($user, $data['type']);

        $this->state->set($telegramId, 'txn.category', $data);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => "Which category?",
            'reply_markup' => json_encode(TransactionKeyboard::categorySelector($categories)),
        ]);
    }

    private function stepToAccount(int|string $telegramId, int|string $chatId, int $messageId, int $toAccountId): void
    {
        $account = $this->ownedAccount($telegramId, $toAccountId);
        if (!$account) {
            return;
        }

        $data                   = $this->state->data($telegramId);
        $data['to_account_id']  = $toAccountId;
        $data['to_account_name'] = $account->name;

        $this->state->set($telegramId, 'txn.amount', $data);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "Amount to transfer?",
        ]);
    }

    private function stepCategory(int|string $telegramId, int|string $chatId, int $messageId, int $categoryId): void
    {
        $category = $this->ownedCategory($telegramId, $categoryId);
        if (!$category) {
            return;
        }

        $data                  = $this->state->data($telegramId);
        $data['category_id']   = $categoryId;
        $data['category_name'] = ($category->icon ? $category->icon . ' ' : '') . $category->name;

        $this->state->set($telegramId, 'txn.amount', $data);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "Amount?",
        ]);
    }

    private function stepAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Please enter a positive number (e.g. 25 or 9.99).']);
            return;
        }

        $data           = $this->state->data($telegramId);
        $data['amount'] = (float) $text;

        $this->state->set($telegramId, 'txn.note', $data);

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => "Add a note? (optional)",
            'reply_markup' => json_encode(TransactionKeyboard::noteStep()),
        ]);
    }

    private function stepNote(int|string $telegramId, int|string $chatId, string $text): void
    {
        $data = $this->state->data($telegramId);
        $data['description'] = $text;

        $this->state->set($telegramId, 'txn.confirm', $data);
        $this->showConfirmation($telegramId, $chatId, $data);
    }

    private function stepNoteSkip(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data = $this->state->data($telegramId);

        $this->state->set($telegramId, 'txn.confirm', $data);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $this->summaryText($data),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(TransactionKeyboard::confirmation()),
        ]);
    }

    private function showConfirmation(int|string $telegramId, int|string $chatId, array $data): void
    {
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $this->summaryText($data),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(TransactionKeyboard::confirmation()),
        ]);
    }

    // ── Confirm / Cancel ────────────────────────────────────────────────────

    private function doConfirm(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data = $this->state->data($telegramId);
        $user = User::where('telegram_id', $telegramId)->firstOrFail();

        $transaction = $this->transactionService->createTransaction($user, $data);

        $this->state->clear($telegramId);

        $typeEmoji = match ($data['type']) {
            'income'   => '💰',
            'expense'  => '💸',
            'transfer' => '🔄',
        };

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "{$typeEmoji} Transaction saved!\n\n" . $this->summaryText($data),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function doCancel(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $this->state->clear($telegramId);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "❌ Transaction cancelled.",
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function summaryText(array $data): string
    {
        $typeLabel = match ($data['type']) {
            'income'   => '💰 Income',
            'expense'  => '💸 Expense',
            'transfer' => '🔄 Transfer',
        };

        $lines = [
            "📝 *Transaction Summary*\n",
            "Type: {$typeLabel}",
        ];

        if ($data['type'] === 'transfer') {
            $lines[] = "From: {$data['account_name']}";
            $lines[] = "To: " . ($data['to_account_name'] ?? '—');
        } else {
            $lines[] = "Account: {$data['account_name']}";
            $lines[] = "Category: " . ($data['category_name'] ?? '—');
        }

        $currency = $data['currency'] ?? '';
        $amount   = isset($data['amount']) ? number_format($data['amount'], 2) : '—';
        $lines[]  = "Amount: {$currency} {$amount}";

        if (!empty($data['description'])) {
            $lines[] = "Note: {$data['description']}";
        }

        return implode("\n", $lines);
    }

    private function ownedAccount(int|string $telegramId, int $accountId): ?Account
    {
        $user = User::where('telegram_id', $telegramId)->first();

        return $user?->accounts()->find($accountId);
    }

    private function ownedCategory(int|string $telegramId, int $categoryId): ?Category
    {
        $user = User::where('telegram_id', $telegramId)->first();

        return $user?->categories()->find($categoryId);
    }
}
