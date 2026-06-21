<?php

namespace App\Telegram\Keyboards;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class TransactionKeyboard
{
    public static function typeSelector(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '💸 Expense',  'callback_data' => 'txn_type:expense'],
                    ['text' => '💰 Income',   'callback_data' => 'txn_type:income'],
                ],
                [
                    ['text' => '🔄 Transfer', 'callback_data' => 'txn_type:transfer'],
                ],
            ],
        ];
    }

    public static function accountSelector(Collection $accounts, string $callbackPrefix = 'txn_account'): array
    {
        $rows = $accounts->map(fn (Account $a) => [[
            'text'          => self::accountLabel($a),
            'callback_data' => "{$callbackPrefix}:{$a->id}",
        ]])->values()->toArray();

        $rows[] = [['text' => '❌ Cancel', 'callback_data' => 'txn:cancel']];

        return ['inline_keyboard' => $rows];
    }

    public static function categorySelector(Collection $categories): array
    {
        $buttons = $categories->map(fn (Category $c) => [
            'text'          => ($c->icon ? $c->icon . ' ' : '') . $c->name,
            'callback_data' => "txn_category:{$c->id}",
        ])->values()->toArray();

        $rows   = array_chunk($buttons, 2);
        $rows[] = [['text' => '❌ Cancel', 'callback_data' => 'txn:cancel']];

        return ['inline_keyboard' => $rows];
    }

    public static function noteStep(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '⏭ Skip note', 'callback_data' => 'txn_note:skip'],
                ['text' => '❌ Cancel',    'callback_data' => 'txn:cancel'],
            ]],
        ];
    }

    public static function confirmation(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Confirm', 'callback_data' => 'txn:confirm'],
                ['text' => '❌ Cancel',  'callback_data' => 'txn:cancel'],
            ]],
        ];
    }

    // ── AI-specific keyboards ────────────────────────────────────────────────

    public static function aiPreview(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Confirm', 'callback_data' => 'ai_txn:confirm'],
                    ['text' => '❌ Cancel',  'callback_data' => 'ai_txn:cancel'],
                ],
                [
                    ['text' => '✏️ Category', 'callback_data' => 'ai_txn:change_category'],
                    ['text' => '✏️ Account',  'callback_data' => 'ai_txn:change_account'],
                ],
            ],
        ];
    }

    public static function aiCategorySelector(Collection $categories): array
    {
        $buttons = $categories->map(fn (Category $c) => [
            'text'          => ($c->icon ? $c->icon . ' ' : '') . $c->name,
            'callback_data' => "ai_txn_category:{$c->id}",
        ])->values()->toArray();

        $rows   = array_chunk($buttons, 2);
        $rows[] = [['text' => '« Back', 'callback_data' => 'ai_txn:back_to_preview']];

        return ['inline_keyboard' => $rows];
    }

    public static function aiAccountSelector(Collection $accounts): array
    {
        $rows = $accounts->map(fn (Account $a) => [[
            'text'          => self::accountLabel($a),
            'callback_data' => "ai_txn_account:{$a->id}",
        ]])->values()->toArray();

        $rows[] = [['text' => '« Back', 'callback_data' => 'ai_txn:back_to_preview']];

        return ['inline_keyboard' => $rows];
    }

    private static function accountLabel(Account $a): string
    {
        $icons = ['cash' => '💵', 'card' => '💳', 'bank' => '🏦', 'e-wallet' => '📱', 'credit' => '💸'];
        $icon  = $icons[$a->type] ?? '💰';

        return "{$icon} {$a->name} (" . number_format($a->current_balance, 2) . " {$a->currency})";
    }
}
