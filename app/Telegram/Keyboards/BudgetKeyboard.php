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
                ['text' => "📊 {$budget->name}", 'callback_data' => "budget_view:{$budget->id}"],
                ['text' => '🗑', 'callback_data' => "budget_delete:{$budget->id}"],
            ];
        }

        $buttons[] = [['text' => __('bot.btn_add_budget'), 'callback_data' => 'budget:add']];

        return ['inline_keyboard' => $buttons];
    }

    public static function periodSelector(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => __('bot.btn_period_monthly'), 'callback_data' => 'budget_period:monthly'],
                ['text' => __('bot.btn_period_weekly'),  'callback_data' => 'budget_period:weekly'],
                ['text' => __('bot.btn_period_yearly'),  'callback_data' => 'budget_period:yearly'],
            ],
        ]];
    }
}
