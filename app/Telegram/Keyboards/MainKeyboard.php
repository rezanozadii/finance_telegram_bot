<?php

namespace App\Telegram\Keyboards;

class MainKeyboard
{
    public static function main(string $lang = 'en'): array
    {
        if ($lang === 'fa') {
            return [
                'keyboard' => [
                    [
                        ['text' => '➕ افزودن تراکنش'],
                        ['text' => '💰 موجودی'],
                    ],
                    [
                        ['text' => '📊 گزارش'],
                        ['text' => '📋 تراکنش‌ها'],
                    ],
                    [
                        ['text' => '🎯 اهداف'],
                        ['text' => '💼 بودجه‌ها'],
                    ],
                    [
                        ['text' => '👥 دوستان'],
                        ['text' => '🤖 هوش مصنوعی'],
                    ],
                    [
                        ['text' => '🏦 حساب‌ها'],
                        ['text' => '⚙️ تنظیمات'],
                    ],
                ],
                'resize_keyboard'   => true,
                'persistent'        => true,
            ];
        }

        return [
            'keyboard' => [
                [
                    ['text' => '➕ Add Transaction'],
                    ['text' => '💰 Balance'],
                ],
                [
                    ['text' => '📊 Report'],
                    ['text' => '📋 Transactions'],
                ],
                [
                    ['text' => '🎯 Goals'],
                    ['text' => '💼 Budgets'],
                ],
                [
                    ['text' => '👥 Friends'],
                    ['text' => '🤖 AI Coach'],
                ],
                [
                    ['text' => '🏦 Accounts'],
                    ['text' => '⚙️ Settings'],
                ],
            ],
            'resize_keyboard'   => true,
            'persistent'        => true,
        ];
    }

    public static function settings(string $lang = 'en'): array
    {
        if ($lang === 'fa') {
            return [
                'inline_keyboard' => [
                    [
                        ['text' => '🌐 زبان',         'callback_data' => 'settings:language'],
                        ['text' => '📂 دسته‌بندی‌ها', 'callback_data' => 'settings:categories'],
                    ],
                    [
                        ['text' => '🔄 تراکنش‌های تکرار', 'callback_data' => 'settings:recurring'],
                        ['text' => '❤️ سلامت مالی',       'callback_data' => 'settings:health'],
                    ],
                    [
                        ['text' => '💡 بینش‌های روزانه', 'callback_data' => 'settings:insights'],
                        ['text' => '🏋️ مشاوره مالی',    'callback_data' => 'settings:coach'],
                    ],
                ],
            ];
        }

        return [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 Language',    'callback_data' => 'settings:language'],
                    ['text' => '📂 Categories',  'callback_data' => 'settings:categories'],
                ],
                [
                    ['text' => '🔄 Recurring',   'callback_data' => 'settings:recurring'],
                    ['text' => '❤️ Health Score', 'callback_data' => 'settings:health'],
                ],
                [
                    ['text' => '💡 Daily Insights', 'callback_data' => 'settings:insights'],
                    ['text' => '🏋️ Coach',          'callback_data' => 'settings:coach'],
                ],
            ],
        ];
    }
}
