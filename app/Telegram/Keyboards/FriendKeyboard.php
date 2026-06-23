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
                ['text' => __('bot.btn_accept'),  'callback_data' => "friend_accept:{$friendship->id}"],
                ['text' => __('bot.btn_decline'), 'callback_data' => "friend_decline:{$friendship->id}"],
            ]],
        ];
    }

    public static function friendList(Collection $friends, bool $hasPending): array
    {
        $rows = $friends->map(fn (User $f) => [[
            'text'          => ($f->username ? '@' . $f->username : $f->display_name),
            'callback_data' => "friend_view:{$f->id}",
        ]])->values()->toArray();

        $footer = [['text' => __('bot.btn_add_friend'), 'callback_data' => 'friend:add']];
        if ($hasPending) {
            $footer[] = ['text' => __('bot.btn_friend_requests'), 'callback_data' => 'friend:requests'];
        }
        $rows[] = $footer;

        return ['inline_keyboard' => $rows];
    }

    public static function friendActions(User $friend): array
    {
        return [
            'inline_keyboard' => [
                [['text' => __('bot.btn_log_expense'), 'callback_data' => "friend_expense:{$friend->id}"]],
                [['text' => __('bot.btn_settle_up'),   'callback_data' => "friend_settle:{$friend->id}"]],
                [['text' => __('bot.btn_back'),         'callback_data' => 'friend:list']],
            ],
        ];
    }

    public static function payerSelector(User $friend): array
    {
        return [
            'inline_keyboard' => [
                [['text' => __('bot.btn_i_paid'),                                            'callback_data' => 'friend_payer:me']],
                [['text' => __('bot.btn_they_paid', ['name' => $friend->display_name]),      'callback_data' => 'friend_payer:them']],
                [['text' => __('bot.btn_cancel'),                                            'callback_data' => 'friend:cancel_expense']],
            ],
        ];
    }

    public static function noteStep(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_skip_note'), 'callback_data' => 'friend_expense_note:skip'],
                ['text' => __('bot.btn_cancel'),    'callback_data' => 'friend:cancel_expense'],
            ]],
        ];
    }

    public static function expenseConfirmation(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_confirm'), 'callback_data' => 'friend_expense:confirm'],
                ['text' => __('bot.btn_cancel'),  'callback_data' => 'friend:cancel_expense'],
            ]],
        ];
    }

    public static function settleConfirmation(User $friend): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_settle_confirm'), 'callback_data' => "friend_settle_confirm:{$friend->id}"],
                ['text' => __('bot.btn_cancel'),          'callback_data' => "friend_view:{$friend->id}"],
            ]],
        ];
    }

    public static function pendingRequests(Collection $friendships): array
    {
        $rows = $friendships->map(fn (Friendship $f) => [[
            'text'          => __('bot.btn_wants_friends', [
                'name' => $f->user->username ? '@' . $f->user->username : $f->user->display_name,
            ]),
            'callback_data' => "friend_request_view:{$f->id}",
        ]])->values()->toArray();

        $rows[] = [['text' => __('bot.btn_back'), 'callback_data' => 'friend:list']];

        return ['inline_keyboard' => $rows];
    }
}
