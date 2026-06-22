<?php

namespace App\Telegram\Keyboards;

use Illuminate\Database\Eloquent\Collection;

class BudgetKeyboard
{
    public static function list(Collection $budgets): array
    {
        $buttons = [];

        foreach ($budgets as $budget) {
            $buttons[] = [
                ['text' => "📊 {$budget->name}", 'callback_data' => "budget:list"],
                ['text' => '🗑', 'callback_data' => "budget_delete:{$budget->id}"],
            ];
        }

        $buttons[] = [['text' => '➕ Add Budget', 'callback_data' => 'budget:add']];

        return ['inline_keyboard' => $buttons];
    }

    public static function periodSelector(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '📅 Monthly', 'callback_data' => 'budget_period:monthly'],
                ['text' => '📆 Weekly',  'callback_data' => 'budget_period:weekly'],
                ['text' => '🗓 Yearly',  'callback_data' => 'budget_period:yearly'],
            ],
        ]];
    }
}
