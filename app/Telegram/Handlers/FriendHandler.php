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

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => "Enter the Telegram username of the person you want to add (e.g. @alice):",
        ]);
    }

    public function handleAddFriendCommand(int|string $telegramId, int|string $chatId, string $username): void
    {
        if ($username === '') {
            $this->state->set($telegramId, 'friend.add_username');
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => "Who do you want to add? Enter their @username:"]);
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
                'chat_id' => $chatId,
                'text'    => "❌ User *" . ltrim($usernameInput, '@') . "* hasn't started this bot yet.",
                'parse_mode' => 'Markdown',
            ]);
            return;
        }

        if ($target->id === $from->id) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => "You can't add yourself as a friend."]);
            return;
        }

        $friendship = $this->friendService->sendRequest($from, $target);

        if (!$friendship) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => "You're already friends or a request already exists."]);
            return;
        }

        // Notify requester
        $targetName = $target->username ? '@' . $target->username : $target->display_name;
        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => "✅ Friend request sent to *{$targetName}*!",
            'parse_mode' => 'Markdown',
        ]);

        // Notify recipient
        $fromName = $from->username ? '@' . $from->username : $from->display_name;
        try {
            Telegram::sendMessage([
                'chat_id'      => $target->telegram_id,
                'text'         => "🤝 *{$fromName}* wants to be your friend on Finance Tracker!",
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
                ? "No pending friend requests."
                : "🔔 *Pending requests* ({$pending->count()})",
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
            'text'         => "🤝 *{$name}* wants to be your friend.",
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
            'text'       => "✅ You're now friends with *{$name}*!",
            'parse_mode' => 'Markdown',
        ]);

        // Notify the requester
        try {
            $acceptorName = User::where('telegram_id', $telegramId)->value('display_name');
            Telegram::sendMessage([
                'chat_id'    => $friendship->user->telegram_id,
                'text'       => "✅ *{$acceptorName}* accepted your friend request!",
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
            'text'       => "❌ Friend request from *{$name}* declined.",
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
            'text'         => "👤 *{$name}*\n\nBalance: {$balanceStr}",
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
            'text'         => "Log a shared expense with *{$friend->display_name}*.\n\nWho paid?",
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
            'text'       => "How much? (enter amount)",
        ]);
    }

    private function stepExpenseAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Please enter a positive number.']);
            return;
        }

        $this->state->set($telegramId, 'friend.expense_note', array_merge(
            $this->state->data($telegramId),
            ['amount' => (float) $text]
        ));

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => "What was it for? (optional description)",
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
            'text'       => "✅ Shared expense logged!\n\n" . $this->expenseSummaryText($data),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function cancelExpense(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $this->state->clear($telegramId);
        Telegram::editMessageText(['chat_id' => $chatId, 'message_id' => $messageId, 'text' => "❌ Cancelled."]);
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
                'text'       => "You and *{$name}* are already settled up! ✅",
                'parse_mode' => 'Markdown',
            ]);
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => "Settle up with *{$name}*?\n\nCurrent balance: " . $this->formatBalances($balances) . "\n\nAll open shared expenses will be marked as settled.",
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
            'text'       => "✅ Settled up with *{$name}*! ({$count} expense(s) marked as settled)",
            'parse_mode' => 'Markdown',
        ]);

        // Notify the friend
        try {
            $myName = $user->username ? '@' . $user->username : $user->display_name;
            Telegram::sendMessage([
                'chat_id'    => $friend->telegram_id,
                'text'       => "✅ *{$myName}* marked all shared expenses between you as settled.",
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
            return '✅ Settled';
        }

        $parts = [];
        foreach ($balances as $currency => $amount) {
            if (abs($amount) < 0.01) {
                continue;
            }
            if ($amount > 0) {
                $parts[] = "they owe you {$currency} " . number_format($amount, 2);
            } else {
                $parts[] = "you owe them {$currency} " . number_format(abs($amount), 2);
            }
        }

        return $parts ? implode(', ', $parts) : '✅ Settled';
    }

    private function expenseSummaryText(array $data): string
    {
        $paidBy  = $data['payer'] === 'me' ? 'You paid' : ($data['friend_name'] . ' paid');
        $owes    = $data['payer'] === 'me' ? $data['friend_name'] . ' owes you' : 'You owe ' . $data['friend_name'];
        $amount  = number_format((float) ($data['amount'] ?? 0), 2);
        $currency = $data['currency'] ?? '';

        $lines = [
            "💸 *Shared Expense*\n",
            "With: {$data['friend_name']}",
            "Paid by: {$paidBy}",
            "Amount: {$currency} {$amount}",
            "Result: {$owes} {$currency} {$amount}",
        ];

        if (!empty($data['description'])) {
            $lines[] = "Note: {$data['description']}";
        }

        return implode("\n", $lines);
    }
}
