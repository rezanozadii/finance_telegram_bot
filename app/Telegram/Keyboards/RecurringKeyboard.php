<?php

namespace App\Telegram\Keyboards;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringOccurrence;
use App\Models\RecurringTemplate;
use Illuminate\Database\Eloquent\Collection;

class RecurringKeyboard
{
    public static function typeSelector(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '💸 Expense', 'callback_data' => 'rec_type:expense'],
                ['text' => '💰 Income',  'callback_data' => 'rec_type:income'],
            ]],
        ];
    }

    public static function categorySelector(Collection $categories): array
    {
        $buttons = $categories->map(fn (Category $c) => [
            'text'          => ($c->icon ? $c->icon . ' ' : '') . $c->name,
            'callback_data' => "rec_category:{$c->id}",
        ])->values()->toArray();

        $rows   = array_chunk($buttons, 2);
        $rows[] = [['text' => '❌ Cancel', 'callback_data' => 'rec:cancel']];

        return ['inline_keyboard' => $rows];
    }

    public static function accountSelector(Collection $accounts): array
    {
        $rows = $accounts->map(fn (Account $a) => [[
            'text'          => self::accountLabel($a),
            'callback_data' => "rec_account:{$a->id}",
        ]])->values()->toArray();

        $rows[] = [['text' => '❌ Cancel', 'callback_data' => 'rec:cancel']];

        return ['inline_keyboard' => $rows];
    }

    public static function frequencySelector(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '📅 Daily',   'callback_data' => 'rec_freq:daily'],
                    ['text' => '📅 Weekly',  'callback_data' => 'rec_freq:weekly'],
                ],
                [
                    ['text' => '📅 Monthly', 'callback_data' => 'rec_freq:monthly'],
                    ['text' => '📅 Yearly',  'callback_data' => 'rec_freq:yearly'],
                ],
            ],
        ];
    }

    public static function reminderSelector(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '1 day before',  'callback_data' => 'rec_reminder:1'],
                    ['text' => '2 days before', 'callback_data' => 'rec_reminder:2'],
                    ['text' => '3 days before', 'callback_data' => 'rec_reminder:3'],
                ],
                [
                    ['text' => '🔕 No reminder', 'callback_data' => 'rec_reminder:0'],
                ],
            ],
        ];
    }

    public static function dueConfirmation(RecurringOccurrence $occurrence): array
    {
        $id = $occurrence->id;

        return [
            'inline_keyboard' => [
                [['text' => '✅ Log it',        'callback_data' => "rec_confirm:{$id}"]],
                [
                    ['text' => '✏️ Edit amount', 'callback_data' => "rec_edit_amount:{$id}"],
                    ['text' => '⏭ Skip',         'callback_data' => "rec_skip:{$id}"],
                ],
            ],
        ];
    }

    public static function templateList(Collection $templates): array
    {
        $rows = $templates->map(fn (RecurringTemplate $t) => [[
            'text'          => ($t->category?->icon ? $t->category->icon . ' ' : '') . $t->description . " ({$t->currency} {$t->amount})",
            'callback_data' => "rec_template:{$t->id}",
        ]])->values()->toArray();

        $rows[] = [['text' => '➕ Add Recurring', 'callback_data' => 'rec:add']];

        return ['inline_keyboard' => $rows];
    }

    public static function templateActions(RecurringTemplate $template): array
    {
        return [
            'inline_keyboard' => [
                [['text' => '🔴 Deactivate', 'callback_data' => "rec_deactivate:{$template->id}"]],
                [['text' => '« Back',         'callback_data' => 'rec:list']],
            ],
        ];
    }

    public static function confirmDeactivate(RecurringTemplate $template): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Yes, deactivate', 'callback_data' => "rec_deactivate_confirm:{$template->id}"],
                ['text' => '❌ Cancel',           'callback_data' => "rec_template:{$template->id}"],
            ]],
        ];
    }

    private static function accountLabel(Account $a): string
    {
        $icons = ['cash' => '💵', 'card' => '💳', 'bank' => '🏦', 'e-wallet' => '📱', 'credit' => '💸'];

        return ($icons[$a->type] ?? '💰') . " {$a->name} ({$a->currency})";
    }
}
