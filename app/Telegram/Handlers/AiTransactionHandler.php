<?php

namespace App\Telegram\Handlers;

use App\Models\CategorizationRule;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AiParseResult;
use App\Services\AiParsingService;
use App\Services\CategoryService;
use App\Services\ConversationStateService;
use App\Services\TransactionService;
use App\Telegram\Keyboards\TransactionKeyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class AiTransactionHandler
{
    public function __construct(
        private AiParsingService $aiParser,
        private TransactionService $transactionService,
        private AccountService $accountService,
        private CategoryService $categoryService,
        private ConversationStateService $state,
    ) {}

    // ── Entry — called for every free-text message with no active state ──────

    public function handle(Message $message): void
    {
        $text       = trim($message->getText() ?? '');
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();

        if (!$this->looksLikeTransaction($text)) {
            return;
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user || $user->accounts()->where('is_archived', false)->doesntExist()) {
            return;
        }

        if (!config('services.deepseek.api_key')) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.ai_not_configured')]);
            return;
        }

        // Show typing indicator while parsing
        Telegram::sendChatAction(['chat_id' => $chatId, 'action' => 'typing']);

        $result = $this->aiParser->parse($user, $text);

        if (!$result || $result->amount <= 0 || $result->accountId === null) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.ai_failed')]);
            return;
        }

        // Persist into conversation state so callbacks can access it
        $this->state->set($telegramId, 'ai_txn.preview', array_merge(
            $result->toArray(),
            ['raw_input_text' => $text]
        ));

        $this->sendPreview($chatId, $result);
    }

    // ── Callback routing ─────────────────────────────────────────────────────

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'ai_txn:confirm'              => $this->doConfirm($telegramId, $chatId, $messageId),
            $action === 'ai_txn:cancel'               => $this->doCancel($telegramId, $chatId, $messageId),
            $action === 'ai_txn:change_category'      => $this->showCategoryPicker($telegramId, $chatId, $messageId),
            $action === 'ai_txn:change_account'       => $this->showAccountPicker($telegramId, $chatId, $messageId),
            $action === 'ai_txn:back_to_preview'      => $this->backToPreview($telegramId, $chatId, $messageId),
            str_starts_with($action, 'ai_txn_category:') => $this->pickCategory($telegramId, $chatId, $messageId, (int) substr($action, 16)),
            str_starts_with($action, 'ai_txn_account:')  => $this->pickAccount($telegramId, $chatId, $messageId, (int) substr($action, 15)),
            default => null,
        };
    }

    // ── Preview ──────────────────────────────────────────────────────────────

    private function sendPreview(int|string $chatId, AiParseResult $result): void
    {
        $warnings = [];
        if ($result->accountUnmatched) {
            $warnings[] = "⚠️ Account not found — defaulting to *{$result->accountName}*";
        }
        if ($result->categoryUnmatched) {
            $warnings[] = "⚠️ Category not recognised";
        }
        if ($result->confidence < 0.7) {
            $warnings[] = "⚠️ Low confidence (" . round($result->confidence * 100) . "%) — please review";
        }

        $warningText = $warnings ? "\n\n" . implode("\n", $warnings) : '';

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.ai_confirm', ['summary' => $this->summaryText($result->toArray()) . $warningText]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(TransactionKeyboard::aiPreview()),
        ]);
    }

    // ── Confirm ──────────────────────────────────────────────────────────────

    private function doConfirm(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data = $this->state->data($telegramId);
        $user = User::where('telegram_id', $telegramId)->firstOrFail();

        $rawText = $data['raw_input_text'] ?? '';
        $result  = AiParseResult::fromArray($data);

        $this->transactionService->createTransaction(
            $user,
            $result->toTransactionData($rawText)
        );

        $this->state->clear($telegramId);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.ai_saved') . "\n\n" . $this->summaryText($data),
            'parse_mode' => 'Markdown',
        ]);
    }

    // ── Cancel ───────────────────────────────────────────────────────────────

    private function doCancel(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $this->state->clear($telegramId);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.cancelled'),
        ]);
    }

    // ── Category picker ──────────────────────────────────────────────────────

    private function showCategoryPicker(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data = $this->state->data($telegramId);
        $user = User::where('telegram_id', $telegramId)->firstOrFail();
        $type = $data['type'] ?? 'expense';

        $categories = $this->categoryService->listForUser($user)->where('type', $type)->where('parent_id', null);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.ai_choose_category'),
            'reply_markup' => json_encode(TransactionKeyboard::aiCategorySelector($categories)),
        ]);
    }

    private function pickCategory(int|string $telegramId, int|string $chatId, int $messageId, int $categoryId): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $category = $user->categories()->find($categoryId);

        if (!$category) {
            return;
        }

        $data                   = $this->state->data($telegramId);
        $data['category_id']    = $category->id;
        $data['category_name']  = ($category->icon ? $category->icon . ' ' : '') . $category->name;

        // Learn: if a merchant was identified, upsert a CategorizationRule
        if (!empty($data['merchant'])) {
            CategorizationRule::updateOrCreate(
                ['user_id' => $user->id, 'merchant_or_keyword' => strtolower($data['merchant'])],
                ['category_id' => $category->id]
            );
        }

        $this->state->set($telegramId, 'ai_txn.preview', $data);

        $result = AiParseResult::fromArray($data);
        $this->editPreview($chatId, $messageId, $result);
    }

    // ── Account picker ───────────────────────────────────────────────────────

    private function showAccountPicker(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $accounts = $this->accountService->listActive($user);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.ai_choose_account'),
            'reply_markup' => json_encode(TransactionKeyboard::aiAccountSelector($accounts)),
        ]);
    }

    private function pickAccount(int|string $telegramId, int|string $chatId, int $messageId, int $accountId): void
    {
        $user    = User::where('telegram_id', $telegramId)->firstOrFail();
        $account = $user->accounts()->find($accountId);

        if (!$account) {
            return;
        }

        $data                 = $this->state->data($telegramId);
        $data['account_id']   = $account->id;
        $data['account_name'] = $account->name;
        $data['currency']     = $account->currency;

        $this->state->set($telegramId, 'ai_txn.preview', $data);

        $result = AiParseResult::fromArray($data);
        $this->editPreview($chatId, $messageId, $result);
    }

    // ── Back to preview ──────────────────────────────────────────────────────

    private function backToPreview(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data   = $this->state->data($telegramId);
        $result = AiParseResult::fromArray($data);
        $this->editPreview($chatId, $messageId, $result);
    }

    private function editPreview(int|string $chatId, int $messageId, AiParseResult $result): void
    {
        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.ai_confirm', ['summary' => $this->summaryText($result->toArray())]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(TransactionKeyboard::aiPreview()),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function looksLikeTransaction(string $text): bool
    {
        return strlen($text) > 2 && preg_match('/\d/', $text) === 1;
    }

    private function summaryText(array $data): string
    {
        $type = $data['type'] ?? 'expense';
        $typeLabel = match ($type) {
            'income'   => '💰 ' . __('bot.report_income'),
            'expense'  => '💸 ' . __('bot.report_expenses'),
            'transfer' => '🔄 ' . __('bot.txn_transfer'),
        };

        $currency = $data['currency'] ?? '';
        $amount   = isset($data['amount']) ? number_format((float) $data['amount'], 2) : '—';

        $lines = [__('bot.txn_label_type') . ": {$typeLabel}"];

        if ($type === 'transfer') {
            $lines[] = __('bot.txn_label_from') . ": " . ($data['account_name'] ?? '—');
            $lines[] = __('bot.txn_label_to')   . ": " . ($data['to_account_name'] ?? '—');
        } else {
            $lines[] = __('bot.txn_label_account')  . ": " . ($data['account_name'] ?? '—');
            $lines[] = __('bot.txn_label_category') . ": " . ($data['category_name'] ?? '—');
        }

        $lines[] = __('bot.txn_label_amount') . ": {$currency} {$amount}";

        if (!empty($data['merchant'])) {
            $lines[] = __('bot.txn_label_merchant') . ": {$data['merchant']}";
        }
        if (!empty($data['description'])) {
            $lines[] = __('bot.txn_label_note') . ": {$data['description']}";
        }
        if (!empty($data['occurred_at'])) {
            try {
                $lines[] = __('bot.txn_label_date') . ": " . \Carbon\Carbon::parse($data['occurred_at'])->format('M d, Y');
            } catch (\Throwable) {}
        }

        return implode("\n", $lines);
    }
}
