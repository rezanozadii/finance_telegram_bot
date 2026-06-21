<?php

namespace App\Telegram\Handlers;

use App\Models\Friendship;
use App\Models\User;
use App\Services\ConversationStateService;
use App\Services\FriendService;
use App\Telegram\Keyboards\FriendKeyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class FriendHandler
{
    public function __construct(
        private FriendService $friendService,
        private ConversationStateService $state,
    ) {}

    // ── Message (text input) steps ──────────────────────────────────────────

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'friend.add_username'    => $this->stepAddUsername($telegramId, $chatId, $text),
            'friend.expense_amount'  => $this->stepExpenseAmount($telegramId, $chatId, $text),
            'friend.expense_note'    => $this->stepExpenseNote($telegramId, $chatId, $text),
            default => null,
        };
    }

    // ── Callback routing ─────────────────────────────────────────────────────

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'friend:list'                           => $this->showList($telegramId, $chatId, $messageId),
            $action === 'friend:add'                            => $this->startAddFriend($telegramId, $chatId),
            $action === 'friend:requests'                       => $this->showRequests($telegramId, $chatId, $messageId),
            $action === 'friend:cancel_expense'                 => $this->cancelExpense($telegramId, $chatId, $messageId),
            str_starts_with($action, 'friend_accept:')         => $this->acceptRequest($telegramId, $chatId, $messageId, (int) substr($action, 13)),
            str_starts_with($action, 'friend_decline:')        => $this->declineRequest($telegramId, $chatId, $messageId, (int) substr($action, 14)),
            str_starts_with($action, 'friend_request_view:')   => $this->viewRequest($telegramId, $chatId, $messageId, (int) substr($action, 20)),
            str_starts_with($action, 'friend_view:')           => $this->viewFriend($telegramId, $chatId, $messageId, (int) substr($action, 12)),
            str_starts_with($action, 'friend_expense:') && $action !== 'friend_expense:confirm' => $this->startExpense($telegramId, $chatId, $messageId, (int) substr($action, 15)),
            str_starts_with($action, 'friend_payer:')          => $this->stepPayer($telegramId, $chatId, $messageId, substr($action, 13)),
            $action === 'friend_expense_note:skip'              => $this->stepNoteSkip($telegramId, $chatId, $messageId),
            $action === 'friend_expense:confirm'                => $this->confirmExpense($telegramId, $chatId, $messageId),
            str_starts_with($action, 'friend_settle:') && !str_contains($action, '_confirm') => $this->startSettle($telegramId, $chatId, $messageId, (int) substr($action, 14)),
            str_starts_with($action, 'friend_settle_confirm:') => $this->doSettle($telegramId, $chatId, $messageId, (int) substr($action, 22)),
            default => null,
        };
    }

    // ── /friends list ────────────────────────────────────────────────────────

    public function showList(int|string $telegramId, int|string $chatId, ?int $messageId = null): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $friends  = $this->friendService->getFriends($user);
        $pending  = $this->friendService->getIncomingRequests($user);

        if ($friends->isEmpty() && $pending->isEmpty()) {
            $text = "👥 *Friends*\n\nYou have no friends on the bot yet.\nSend their username with /addfriend or tap Add Friend.";
        } else {
            $lines = ["👥 *Friends* ({$friends->count()})\n"];
            foreach ($friends as $f) {
                $balances    = $this->friendService->getBalance($user, $f);
                $balanceStr  = $this->formatBalances($balances);
                $name        = $f->username ? '@' . $f->username : $f->display_name;
                $lines[]     = "{$name} · {$balanceStr}";
            }
            if ($pending->isNotEmpty()) {
                $lines[] = "\n🔔 {$pending->count()} pending friend request(s)";
            }
            $text = implode("\n", $lines);
        }

        $payload = [
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::friendList($friends, $pending->isNotEmpty())),
        ];

        $messageId
            ? Telegram::editMessageText(array_merge($payload, ['message_id' => $messageId]))
            : Telegram::sendMessage($payload);
    }

    // ── Add friend flow ──────────────────────────────────────────────────────

    public function startAddFriend(int|string $telegramId, int|string $chatId): void
    {
        $this->state->set($telegramId, 'friend.add_username');

        Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.friend_ask_username')]);
    }

    public function handleAddFriendCommand(int|string $telegramId, int|string $chatId, string $username): void
    {
        if ($username === '') {
            $this->state->set($telegramId, 'friend.add_username');
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.friend_ask_username')]);
            return;
        }

        $this->doSendRequest($telegramId, $chatId, $username);
    }

    private function stepAddUsername(int|string $telegramId, int|string $chatId, string $text): void
    {
        $this->state->clear($telegramId);
        $this->doSendRequest($telegramId, $chatId, $text);
    }

    private function doSendRequest(int|string $telegramId, int|string $chatId, string $usernameInput): void
    {
        $from   = User::where('telegram_id', $telegramId)->firstOrFail();
        $target = $this->friendService->findByUsername($usernameInput);

        if (!$target) {
            Telegram::sendMessage([
                'chat_id'    => $chatId,
                'text'       => __('bot.friend_not_found', ['username' => ltrim($usernameInput, '@')]),
                'parse_mode' => 'Markdown',
            ]);
            return;
        }

        if ($target->id === $from->id) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.friend_self')]);
            return;
        }

        $friendship = $this->friendService->sendRequest($from, $target);

        if (!$friendship) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.friend_already')]);
            return;
        }

        // Notify requester
        $targetName = $target->username ? '@' . $target->username : $target->display_name;
        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.friend_request_sent', ['name' => $targetName]),
            'parse_mode' => 'Markdown',
        ]);

        // Notify recipient
        $fromName = $from->username ? '@' . $from->username : $from->display_name;
        try {
            Telegram::sendMessage([
                'chat_id'      => $target->telegram_id,
                'text'         => __('bot.friend_request_received', ['name' => $fromName]),
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode(FriendKeyboard::friendRequest($friendship)),
            ]);
        } catch (\Throwable) {
            // Recipient may have blocked the bot
        }
    }

    // ── Friend request accept/decline ────────────────────────────────────────

    private function showRequests(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $user    = User::where('telegram_id', $telegramId)->firstOrFail();
        $pending = $this->friendService->getIncomingRequests($user);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $pending->isEmpty()
                ? __('bot.friend_no_pending')
                : __('bot.friend_pending_title', ['count' => $pending->count()]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::pendingRequests($pending)),
        ]);
    }

    private function viewRequest(int|string $telegramId, int|string $chatId, int $messageId, int $friendshipId): void
    {
        $friendship = $this->incomingFriendship($telegramId, $friendshipId);
        if (!$friendship) {
            return;
        }

        $name = $friendship->user->username
            ? '@' . $friendship->user->username
            : $friendship->user->display_name;

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.friend_request_received', ['name' => $name]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::friendRequest($friendship)),
        ]);
    }

    private function acceptRequest(int|string $telegramId, int|string $chatId, int $messageId, int $friendshipId): void
    {
        $friendship = $this->incomingFriendship($telegramId, $friendshipId);
        if (!$friendship) {
            return;
        }

        $this->friendService->acceptRequest($friendship);

        $name = $friendship->user->username ? '@' . $friendship->user->username : $friendship->user->display_name;

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.friend_accepted', ['name' => $name]),
            'parse_mode' => 'Markdown',
        ]);

        // Notify the requester
        try {
            $acceptorName = User::where('telegram_id', $telegramId)->value('display_name');
            Telegram::sendMessage([
                'chat_id'    => $friendship->user->telegram_id,
                'text'       => __('bot.friend_accept_notify', ['name' => $acceptorName]),
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable) {}
    }

    private function declineRequest(int|string $telegramId, int|string $chatId, int $messageId, int $friendshipId): void
    {
        $friendship = $this->incomingFriendship($telegramId, $friendshipId);
        if (!$friendship) {
            return;
        }

        $name = $friendship->user->username ? '@' . $friendship->user->username : $friendship->user->display_name;
        $this->friendService->declineRequest($friendship);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.friend_declined', ['name' => $name]),
            'parse_mode' => 'Markdown',
        ]);
    }

    // ── View friend ──────────────────────────────────────────────────────────

    private function viewFriend(int|string $telegramId, int|string $chatId, int $messageId, int $friendUserId): void
    {
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $friend = User::find($friendUserId);

        if (!$friend || !$this->friendService->areFriends($user, $friend)) {
            return;
        }

        $balances    = $this->friendService->getBalance($user, $friend);
        $balanceStr  = $this->formatBalances($balances);
        $name        = $friend->username ? '@' . $friend->username : $friend->display_name;

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => "👤 *{$name}*\n\n{$balanceStr}",
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::friendActions($friend)),
        ]);
    }

    // ── Shared expense flow ──────────────────────────────────────────────────

    private function startExpense(int|string $telegramId, int|string $chatId, int $messageId, int $friendUserId): void
    {
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $friend = User::find($friendUserId);

        if (!$friend || !$this->friendService->areFriends($user, $friend)) {
            return;
        }

        $this->state->set($telegramId, 'friend.expense_payer', [
            'friend_id'   => $friend->id,
            'friend_name' => $friend->display_name,
            'currency'    => $user->default_currency,
        ]);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.friend_expense_who_paid', ['name' => $friend->display_name]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::payerSelector($friend)),
        ]);
    }

    private function stepPayer(int|string $telegramId, int|string $chatId, int $messageId, string $payer): void
    {
        $this->state->set($telegramId, 'friend.expense_amount', array_merge(
            $this->state->data($telegramId),
            ['payer' => $payer]
        ));

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.friend_expense_ask_amount'),
        ]);
    }

    private function stepExpenseAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.enter_positive_number')]);
            return;
        }

        $this->state->set($telegramId, 'friend.expense_note', array_merge(
            $this->state->data($telegramId),
            ['amount' => (float) $text]
        ));

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.friend_expense_ask_note'),
            'reply_markup' => json_encode(FriendKeyboard::noteStep()),
        ]);
    }

    private function stepExpenseNote(int|string $telegramId, int|string $chatId, string $text): void
    {
        $data = array_merge($this->state->data($telegramId), ['description' => $text]);
        $this->state->set($telegramId, 'friend.expense_confirm', $data);
        $this->showExpenseSummary($chatId, $data);
    }

    private function stepNoteSkip(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data = array_merge($this->state->data($telegramId), ['description' => null]);
        $this->state->set($telegramId, 'friend.expense_confirm', $data);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $this->expenseSummaryText($data),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::expenseConfirmation()),
        ]);
    }

    private function showExpenseSummary(int|string $chatId, array $data): void
    {
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $this->expenseSummaryText($data),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::expenseConfirmation()),
        ]);
    }

    private function confirmExpense(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $data   = $this->state->data($telegramId);
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $friend = User::find($data['friend_id']);

        if (!$friend) {
            $this->state->clear($telegramId);
            return;
        }

        // 'me' paid → from=me, to=friend (friend owes me)
        // 'them' paid → from=friend, to=me (I owe friend)
        [$from, $to] = $data['payer'] === 'me'
            ? [$user, $friend]
            : [$friend, $user];

        $this->friendService->logSharedExpense(
            $from,
            $to,
            (float) $data['amount'],
            $data['currency'],
            $data['description'] ?? '',
        );

        $this->state->clear($telegramId);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.friend_expense_logged', ['summary' => $this->expenseSummaryText($data)]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function cancelExpense(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $this->state->clear($telegramId);
        Telegram::editMessageText(['chat_id' => $chatId, 'message_id' => $messageId, 'text' => __('bot.cancelled')]);
    }

    // ── Settle up ────────────────────────────────────────────────────────────

    private function startSettle(int|string $telegramId, int|string $chatId, int $messageId, int $friendUserId): void
    {
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $friend = User::find($friendUserId);

        if (!$friend || !$this->friendService->areFriends($user, $friend)) {
            return;
        }

        $balances = $this->friendService->getBalance($user, $friend);
        $name     = $friend->username ? '@' . $friend->username : $friend->display_name;

        if (empty($balances) || array_sum($balances) == 0.0) {
            Telegram::editMessageText([
                'chat_id'    => $chatId,
                'message_id' => $messageId,
                'text'       => __('bot.friend_already_settled', ['name' => $name]),
                'parse_mode' => 'Markdown',
            ]);
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.friend_settle_confirm', ['name' => $name, 'balance' => $this->formatBalances($balances)]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(FriendKeyboard::settleConfirmation($friend)),
        ]);
    }

    private function doSettle(int|string $telegramId, int|string $chatId, int $messageId, int $friendUserId): void
    {
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $friend = User::find($friendUserId);

        if (!$friend) {
            return;
        }

        $count = $this->friendService->settleUp($user, $friend);
        $name  = $friend->username ? '@' . $friend->username : $friend->display_name;

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.friend_settle_done', ['name' => $name, 'count' => $count]),
            'parse_mode' => 'Markdown',
        ]);

        // Notify the friend
        try {
            $myName = $user->username ? '@' . $user->username : $user->display_name;
            Telegram::sendMessage([
                'chat_id'    => $friend->telegram_id,
                'text'       => __('bot.friend_settle_notify', ['name' => $myName]),
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable) {}
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function incomingFriendship(int|string $telegramId, int $friendshipId): ?Friendship
    {
        $user = User::where('telegram_id', $telegramId)->first();

        return Friendship::where('id', $friendshipId)
            ->where('friend_user_id', $user?->id)
            ->where('status', 'pending')
            ->with('user')
            ->first();
    }

    private function formatBalances(array $balances): string
    {
        if (empty($balances)) {
            return __('bot.friend_settled');
        }

        $parts = [];
        foreach ($balances as $currency => $amount) {
            if (abs($amount) < 0.01) {
                continue;
            }
            if ($amount > 0) {
                $parts[] = __('bot.friend_they_owe', ['currency' => $currency, 'amount' => number_format($amount, 2)]);
            } else {
                $parts[] = __('bot.friend_you_owe', ['currency' => $currency, 'amount' => number_format(abs($amount), 2)]);
            }
        }

        return $parts ? implode(', ', $parts) : __('bot.friend_settled');
    }

    private function expenseSummaryText(array $data): string
    {
        $amount   = number_format((float) ($data['amount'] ?? 0), 2);
        $currency = $data['currency'] ?? '';

        $lines = [
            "💸 *Shared Expense*\n",
            "With: {$data['friend_name']}",
            "Amount: {$currency} {$amount}",
        ];

        if (!empty($data['description'])) {
            $lines[] = "Note: {$data['description']}";
        }

        return implode("\n", $lines);
    }
}
