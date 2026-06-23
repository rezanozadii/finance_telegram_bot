<?php

namespace App\Telegram\Keyboards;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class AccountKeyboard
{
    public static function typeSelector(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.btn_type_cash'),    'callback_data' => 'account_type:cash'],
                    ['text' => __('bot.btn_type_card'),    'callback_data' => 'account_type:card'],
                    ['text' => __('bot.btn_type_bank'),    'callback_data' => 'account_type:bank'],
                ],
                [
                    ['text' => __('bot.btn_type_ewallet'), 'callback_data' => 'account_type:e-wallet'],
                    ['text' => __('bot.btn_type_credit'),  'callback_data' => 'account_type:credit'],
                ],
            ],
        ];
    }

    public static function accountList(Collection $accounts): array
    {
        $rows = $accounts->map(fn (Account $a) => [
            ['text' => "✏️ {$a->name}", 'callback_data' => "account_edit:{$a->id}"],
        ])->values()->toArray();

        $rows[] = [['text' => __('bot.btn_add_account'), 'callback_data' => 'account:add']];

        return ['inline_keyboard' => $rows];
    }

    public static function accountActions(Account $account): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.btn_rename'),  'callback_data' => "account_rename:{$account->id}"],
                    ['text' => __('bot.btn_archive'), 'callback_data' => "account_archive:{$account->id}"],
                ],
                [
                    ['text' => __('bot.btn_back_accounts'), 'callback_data' => 'account:list'],
                ],
            ],
        ];
    }

    public static function confirmArchive(Account $account): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_confirm_archive'), 'callback_data' => "account_archive_confirm:{$account->id}"],
                ['text' => __('bot.btn_cancel'),          'callback_data' => "account_edit:{$account->id}"],
            ]],
        ];
    }
}
