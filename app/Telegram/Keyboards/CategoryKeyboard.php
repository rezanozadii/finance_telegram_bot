<?php

namespace App\Telegram\Keyboards;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryKeyboard
{
    public static function typeSelector(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_expense'), 'callback_data' => 'category_type:expense'],
                ['text' => __('bot.btn_income'),  'callback_data' => 'category_type:income'],
            ]],
        ];
    }

    public static function iconStep(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_skip_icon'), 'callback_data' => 'category_icon:skip'],
            ]],
        ];
    }

    public static function parentSelector(Collection $topLevel): array
    {
        $rows = $topLevel->map(fn (Category $c) => [
            ['text' => ($c->icon ? $c->icon . ' ' : '') . $c->name, 'callback_data' => "category_parent:{$c->id}"],
        ])->values()->toArray();

        $rows[] = [['text' => __('bot.btn_no_parent'), 'callback_data' => 'category_parent:none']];

        return ['inline_keyboard' => $rows];
    }

    public static function manageGrid(Collection $categories): array
    {
        $buttons = $categories->map(fn (Category $c) => [
            'text'          => ($c->icon ? $c->icon . ' ' : '') . $c->name,
            'callback_data' => "category_edit:{$c->id}",
        ])->values()->toArray();

        $rows   = array_chunk($buttons, 2);
        $rows[] = [['text' => __('bot.btn_back'), 'callback_data' => 'category:list']];

        return ['inline_keyboard' => $rows];
    }

    public static function categoryActions(Category $category, bool $canDelete): array
    {
        $actionRow = [
            ['text' => __('bot.btn_rename'),      'callback_data' => "category_rename:{$category->id}"],
            ['text' => __('bot.btn_change_icon'), 'callback_data' => "category_icon_edit:{$category->id}"],
        ];

        $rows = [$actionRow];

        if ($canDelete) {
            $rows[] = [['text' => __('bot.btn_delete'), 'callback_data' => "category_delete:{$category->id}"]];
        }

        $rows[] = [['text' => __('bot.btn_back_list'), 'callback_data' => 'category:manage']];

        return ['inline_keyboard' => $rows];
    }

    public static function confirmDelete(Category $category): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_confirm_delete'), 'callback_data' => "category_delete_confirm:{$category->id}"],
                ['text' => __('bot.btn_cancel'),          'callback_data' => "category_edit:{$category->id}"],
            ]],
        ];
    }

    public static function mainMenu(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => __('bot.btn_add_category'), 'callback_data' => 'category:add'],
                ['text' => __('bot.btn_manage'),       'callback_data' => 'category:manage'],
            ]],
        ];
    }
}
