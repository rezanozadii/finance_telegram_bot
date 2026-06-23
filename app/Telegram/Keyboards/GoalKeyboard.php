<?php

namespace App\Telegram\Keyboards;

use Illuminate\Database\Eloquent\Collection;

class GoalKeyboard
{
    public static function list(Collection $goals): array
    {
        $buttons = [];

        foreach ($goals as $goal) {
            $buttons[] = [
                ['text' => "🎯 {$goal->name}", 'callback_data' => "goal_view:{$goal->id}"],
                ['text' => '🗑', 'callback_data' => "goal_delete:{$goal->id}"],
            ];
        }

        $buttons[] = [['text' => __('bot.btn_add_goal'), 'callback_data' => 'goal:add']];

        return ['inline_keyboard' => $buttons];
    }
}
