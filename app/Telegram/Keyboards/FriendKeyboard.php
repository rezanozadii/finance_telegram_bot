<?php

namespace App\Telegram\Keyboards;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FriendKeyboard
{
    public static function friendRequest(Friendship $friendship): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Accept',  'callback_data' => "friend_accept:{$friendship->id}"],
                ['text' => '❌ Decline', 'callback_data' => "friend_decline:{$friendship->id}"],
            ]],
        ];
    }

    /** Friend list for the /friends hub. */
    public static function friendList(Collection $friends, bool $hasPending): array
    {
        $rows = $friends->map(fn (User $f) => [[
            'text'          => ($f->username ? '@' . $f->username : $f->display_name),
            'callback_data' => "friend_view:{$f->id}",
        ]])->values()->toArray();

        $footer = [['text' => '➕ Add Friend', 'callback_data' => 'friend:add']];
        if ($hasPending) {
            $footer[] = ['text' => '🔔 Requests', 'callback_data' => 'friend:requests'];
        }
        $rows[] = $footer;

        return ['inline_keyboard' => $rows];
    }

    /** Per-friend action menu. */
    public static function friendActions(User $friend): array
    {
        return [
            'inline_keyboard' => [
                [['text' => '💸 Log Shared Expense', 'callback_data' => "friend_expense:{$friend->id}"]],
                [['text' => '✅ Settle Up',           'callback_data' => "friend_settle:{$friend->id}"]],
                [['text' => '« Back',                 'callback_data' => 'friend:list']],
            ],
        ];
    }

    /** Who paid? selector during shared expense creation. */
    public static function payerSelector(User $friend): array
    {
        return [
            'inline_keyboard' => [
                [['text' => '🙋 I paid',            'callback_data' => 'friend_payer:me']],
                [['text' => "🙋 {$friend->display_name} paid", 'callback_data' => 'friend_payer:them']],
                [['text' => '❌ Cancel',             'callback_data' => 'friend:cancel_expense']],
            ],
        ];
    }

    public static function noteStep(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '⏭ Skip note', 'callback_data' => 'friend_expense_note:skip'],
                ['text' => '❌ Cancel',    'callback_data' => 'friend:cancel_expense'],
            ]],
        ];
    }

    public static function expenseConfirmation(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Confirm', 'callback_data' => 'friend_expense:confirm'],
                ['text' => '❌ Cancel',  'callback_data' => 'friend:cancel_expense'],
            ]],
        ];
    }

    public static function settleConfirmation(User $friend): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Settle up', 'callback_data' => "friend_settle_confirm:{$friend->id}"],
                ['text' => '❌ Cancel',    'callback_data' => "friend_view:{$friend->id}"],
            ]],
        ];
    }

    public static function pendingRequests(Collection $friendships): array
    {
        $rows = $friendships->map(fn (Friendship $f) => [[
            'text'          => ($f->user->username ? '@' . $f->user->username : $f->user->display_name) . ' wants to be friends',
            'callback_data' => "friend_request_view:{$f->id}",
        ]])->values()->toArray();

        $rows[] = [['text' => '« Back', 'callback_data' => 'friend:list']];

        return ['inline_keyboard' => $rows];
    }
}
