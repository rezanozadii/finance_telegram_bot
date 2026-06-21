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
                ['text' => '💸 Expense', 'callback_data' => 'category_type:expense'],
                ['text' => '💰 Income',  'callback_data' => 'category_type:income'],
            ]],
        ];
    }

    public static function iconStep(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '⏭ Skip (no icon)', 'callback_data' => 'category_icon:skip'],
            ]],
        ];
    }

    public static function parentSelector(Collection $topLevel): array
    {
        $rows = $topLevel->map(fn (Category $c) => [
            ['text' => ($c->icon ? $c->icon . ' ' : '') . $c->name, 'callback_data' => "category_parent:{$c->id}"],
        ])->values()->toArray();

        $rows[] = [['text' => '— None (top-level)', 'callback_data' => 'category_parent:none']];

        return ['inline_keyboard' => $rows];
    }

    /** Two-column grid of all user categories for the manage screen. */
    public static function manageGrid(Collection $categories): array
    {
        $buttons = $categories->map(fn (Category $c) => [
            'text'          => ($c->icon ? $c->icon . ' ' : '') . $c->name,
            'callback_data' => "category_edit:{$c->id}",
        ])->values()->toArray();

        // Chunk into rows of 2
        $rows   = array_chunk($buttons, 2);
        $rows[] = [['text' => '« Back', 'callback_data' => 'category:list']];

        return ['inline_keyboard' => $rows];
    }

    public static function categoryActions(Category $category, bool $canDelete): array
    {
        $label = ($category->icon ? $category->icon . ' ' : '') . $category->name;

        $actionRow = [
            ['text' => '✏️ Rename',       'callback_data' => "category_rename:{$category->id}"],
            ['text' => '🎨 Change Icon',  'callback_data' => "category_icon_edit:{$category->id}"],
        ];

        $rows = [$actionRow];

        if ($canDelete) {
            $rows[] = [['text' => '🗑 Delete',  'callback_data' => "category_delete:{$category->id}"]];
        }

        $rows[] = [['text' => '« Back to list', 'callback_data' => 'category:manage']];

        return ['inline_keyboard' => $rows];
    }

    public static function confirmDelete(Category $category): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '✅ Yes, delete',   'callback_data' => "category_delete_confirm:{$category->id}"],
                ['text' => '❌ Cancel',         'callback_data' => "category_edit:{$category->id}"],
            ]],
        ];
    }

    public static function mainMenu(): array
    {
        return [
            'inline_keyboard' => [[
                ['text' => '➕ Add Category', 'callback_data' => 'category:add'],
                ['text' => '✏️ Manage',       'callback_data' => 'category:manage'],
            ]],
        ];
    }
}
