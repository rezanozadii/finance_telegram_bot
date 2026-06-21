<?php

namespace App\Telegram\Handlers;

use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use App\Services\ConversationStateService;
use App\Telegram\Keyboards\AccountKeyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class AccountHandler
{
    public function __construct(
        private AccountService $accountService,
        private ConversationStateService $state,
    ) {}

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'account.name'     => $this->stepName($telegramId, $chatId, $text),
            'account.currency' => $this->stepCurrency($telegramId, $chatId, $text),
            'account.balance'  => $this->stepBalance($telegramId, $chatId, $text),
            'account.rename'   => $this->stepRename($telegramId, $chatId, $text),
            default            => null,
        };
    }

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'account:add'                           => $this->startCreation($telegramId, $chatId),
            $action === 'account:list'                          => $this->showList($telegramId, $chatId, $messageId),
            str_starts_with($action, 'account_type:')          => $this->stepType($telegramId, $chatId, substr($action, 13)),
            str_starts_with($action, 'account_edit:')          => $this->showActions($telegramId, $chatId, $messageId, (int) substr($action, 13)),
            str_starts_with($action, 'account_rename:')        => $this->beginRename($telegramId, $chatId, (int) substr($action, 15)),
            str_starts_with($action, 'account_archive:')       => $this->confirmArchive($telegramId, $chatId, $messageId, (int) substr($action, 16)),
            str_starts_with($action, 'account_archive_confirm:') => $this->doArchive($telegramId, $chatId, $messageId, (int) substr($action, 24)),
            default => null,
        };
    }

    public function startCreation(int|string $telegramId, int|string $chatId): void
    {
        $this->state->set($telegramId, 'account.name');
        Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_ask_name')]);
    }

    private function stepName(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_enter_name')]);
            return;
        }

        $this->state->set($telegramId, 'account.type', ['name' => $text]);

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.account_ask_type', ['name' => $text]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(AccountKeyboard::typeSelector()),
        ]);
    }

    private function stepType(int|string $telegramId, int|string $chatId, string $type): void
    {
        $this->state->set($telegramId, 'account.currency', array_merge(
            $this->state->data($telegramId),
            ['type' => $type]
        ));
        Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_ask_currency')]);
    }

    private function stepCurrency(int|string $telegramId, int|string $chatId, string $text): void
    {
        $currency = strtoupper(trim($text));

        if (strlen($currency) < 2 || strlen($currency) > 10) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_invalid_currency')]);
            return;
        }

        $this->state->set($telegramId, 'account.balance', array_merge(
            $this->state->data($telegramId),
            ['currency' => $currency]
        ));
        Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_ask_balance')]);
    }

    private function stepBalance(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text)) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.enter_valid_number')]);
            return;
        }

        $data    = $this->state->data($telegramId);
        $user    = User::where('telegram_id', $telegramId)->firstOrFail();
        $account = $this->accountService->create($user, $data['name'], $data['type'], $data['currency'], (float) $text);

        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.account_created', [
                'icon'     => $this->typeIcon($account->type),
                'name'     => $account->name,
                'currency' => $account->currency,
                'balance'  => number_format((float) $account->current_balance, 2),
            ]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function showList(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $accounts = $this->accountService->listActive($user);

        if ($accounts->isEmpty()) {
            Telegram::editMessageText([
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'text'         => __('bot.account_none'),
                'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '➕ Add Account', 'callback_data' => 'account:add']]]]),
            ]);
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $this->formatAccountList($accounts),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(AccountKeyboard::accountList($accounts)),
        ]);
    }

    private function showActions(int|string $telegramId, int|string $chatId, int $messageId, int $accountId): void
    {
        $account = $this->ownedAccount($telegramId, $accountId);
        if (!$account) {
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $this->typeIcon($account->type) . " *{$account->name}*\nBalance: {$account->currency} " . number_format((float) $account->current_balance, 2),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(AccountKeyboard::accountActions($account)),
        ]);
    }

    private function beginRename(int|string $telegramId, int|string $chatId, int $accountId): void
    {
        $account = $this->ownedAccount($telegramId, $accountId);
        if (!$account) {
            return;
        }

        $this->state->set($telegramId, 'account.rename', ['account_id' => $accountId]);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.account_ask_rename', ['name' => $account->name]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function stepRename(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_enter_name_short')]);
            return;
        }

        $data    = $this->state->data($telegramId);
        $account = $this->ownedAccount($telegramId, $data['account_id'] ?? 0);

        if (!$account) {
            $this->state->clear($telegramId);
            return;
        }

        $old = $account->name;
        $this->accountService->rename($account, $text);
        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.account_renamed', ['old' => $old, 'new' => $text]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function confirmArchive(int|string $telegramId, int|string $chatId, int $messageId, int $accountId): void
    {
        $account = $this->ownedAccount($telegramId, $accountId);
        if (!$account) {
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.account_confirm_archive', ['name' => $account->name]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(AccountKeyboard::confirmArchive($account)),
        ]);
    }

    private function doArchive(int|string $telegramId, int|string $chatId, int $messageId, int $accountId): void
    {
        $account = $this->ownedAccount($telegramId, $accountId);
        if (!$account) {
            return;
        }

        $name = $account->name;
        $this->accountService->archive($account);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.account_archived', ['name' => $name]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function ownedAccount(int|string $telegramId, int $accountId): ?Account
    {
        $user = User::where('telegram_id', $telegramId)->first();
        return $user?->accounts()->find($accountId);
    }

    public function formatAccountList(\Illuminate\Database\Eloquent\Collection $accounts): string
    {
        $lines = $accounts->map(function (Account $a) {
            $icon = $this->typeIcon($a->type);
            return "{$icon} *{$a->name}* ({$a->currency}) — " . number_format((float) $a->current_balance, 2);
        })->join("\n");

        return __('bot.account_list_title') . "\n\n{$lines}";
    }

    private function typeIcon(string $type): string
    {
        return match ($type) {
            'cash'     => '💵',
            'card'     => '💳',
            'bank'     => '🏦',
            'e-wallet' => '📱',
            'credit'   => '💸',
            default    => '💰',
        };
    }
}
