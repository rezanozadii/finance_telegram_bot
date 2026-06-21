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
                    ['text' => '💵 Cash',     'callback_data' => 'account_type:cash'],
                    ['text' => '💳 Card',     'callback_data' => 'account_type:card'],
                    ['text' => '🏦 Bank',     'callback_data' => 'account_type:bank'],
                ],
                [
                    ['text' => '📱 E-Wallet', 'callback_data' => 'account_type:e-wallet'],
                    ['text' => '💸 Credit',   'callback_data' => 'account_type:credit'],
                ],
            ],
        ];
    }

    public static function accountList(Collection $accounts): array
    {
        $rows = $accounts->map(fn (Account $a) => [
            ['text' => "✏️ {$a->name}", 'callback_data' => "account_edit:{$a->id}"],
        ])->values()->toArray();

        $rows[] = [['text' => '➕ Add Account', 'callback_data' => 'account:add']];

        return ['inline_keyboard' => $rows];
    }

    public static function accountActions(Account $account): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '✏️ Rename',  'callback_data' => "account_rename:{$account->id}"],
                    ['text' => '🗃 Archive', 'callback_data' => "account_archive:{$account->id}"],
                ],
                [
                    ['text' => '« Back to Accounts', 'callback_data' => 'account:list'],
                ],
            ],
        ];
    }

    public static function confirmArchive(Account $account): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Yes, archive', 'callback_data' => "account_archive_confirm:{$account->id}"],
                ['text' => '❌ Cancel',        'callback_data' => "account_edit:{$account->id}"],
            ]],
        ];
    }
}
