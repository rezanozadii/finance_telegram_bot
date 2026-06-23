<?php

namespace App\Telegram\Commands;

use App\Services\AccountService;
use App\Services\UserService;
use App\Telegram\Handlers\AccountHandler;
use App\Telegram\Keyboards\MainKeyboard;
use Illuminate\Support\Facades\App;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Register and get started';

    public function handle(): void
    {
        $from     = $this->getUpdate()->getMessage()->getFrom();
        $chatId   = $this->getUpdate()->getMessage()->getChat()->getId();
        $user     = app(UserService::class)->findOrCreate($from);
        $accounts = app(AccountService::class)->listActive($user);
        $lang     = $user->language ?? 'en';

        App::setLocale($lang);

        if ($accounts->isEmpty()) {
            $this->replyWithMessage([
                'text' => __('bot.welcome_new', ['name' => $user->display_name]),
            ]);

            app(AccountHandler::class)->startCreation($from->getId(), $chatId);
            return;
        }

        // Send the persistent bottom keyboard first
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $lang === 'fa'
                ? "✅ منوی اصلی فعال شد. از دکمه‌های زیر استفاده کنید:"
                : "✅ Main menu is ready. Use the buttons below:",
            'reply_markup' => json_encode(MainKeyboard::main($lang)),
        ]);

        // Then send the full feature menu as an inline keyboard
        $name = $user->display_name;
        $text = $lang === 'fa'
            ? "👋 خوش برگشتی *{$name}*!\n\n💼 از منوی زیر یک بخش انتخاب کنید:"
            : "👋 Welcome back, *{$name}*!\n\n💼 Choose a section from the menu below:";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(self::fullMenu($lang)),
        ]);
    }

    public static function fullMenu(string $lang = 'en'): array
    {
        if ($lang === 'fa') {
            return [
                'inline_keyboard' => [
                    [
                        ['text' => '➕ افزودن تراکنش',  'callback_data' => 'txn:start'],
                        ['text' => '📋 تراکنش‌ها',      'callback_data' => 'txn_filter:all'],
                    ],
                    [
                        ['text' => '🏦 حساب‌ها',        'callback_data' => 'account:list'],
                        ['text' => '📂 دسته‌بندی‌ها',   'callback_data' => 'settings:categories'],
                    ],
                    [
                        ['text' => '📊 گزارش ماهانه',   'callback_data' => 'report:month'],
                        ['text' => '📈 گزارش سالانه',   'callback_data' => 'report:year'],
                    ],
                    [
                        ['text' => '🎯 اهداف',          'callback_data' => 'goal:list'],
                        ['text' => '💼 بودجه‌ها',       'callback_data' => 'budget:list'],
                    ],
                    [
                        ['text' => '👥 دوستان',         'callback_data' => 'friend:list'],
                        ['text' => '💰 موجودی',         'callback_data' => 'friend:list'],
                    ],
                    [
                        ['text' => '🔄 تراکنش‌های تکرار', 'callback_data' => 'settings:recurring'],
                        ['text' => '🌐 زبان',            'callback_data' => 'settings:language'],
                    ],
                    [
                        ['text' => '❤️ سلامت مالی',     'callback_data' => 'settings:health'],
                        ['text' => '💡 بینش‌های روزانه', 'callback_data' => 'settings:insights'],
                    ],
                    [
                        ['text' => '🏋️ مشاوره مالی',    'callback_data' => 'settings:coach'],
                        ['text' => '🤖 چت هوش مصنوعی',  'callback_data' => 'ai:start_chat'],
                    ],
                    [
                        ['text' => '🔄 اشتراک‌ها',      'callback_data' => 'ai:subscriptions'],
                        ['text' => '⚙️ تنظیمات',        'callback_data' => 'settings:menu'],
                    ],
                ],
            ];
        }

        return [
            'inline_keyboard' => [
                [
                    ['text' => '➕ Add Transaction',   'callback_data' => 'txn:start'],
                    ['text' => '📋 Transactions',      'callback_data' => 'txn_filter:all'],
                ],
                [
                    ['text' => '🏦 Accounts',          'callback_data' => 'account:list'],
                    ['text' => '📂 Categories',         'callback_data' => 'settings:categories'],
                ],
                [
                    ['text' => '📊 Monthly Report',    'callback_data' => 'report:month'],
                    ['text' => '📈 Yearly Report',     'callback_data' => 'report:year'],
                ],
                [
                    ['text' => '🎯 Goals',             'callback_data' => 'goal:list'],
                    ['text' => '💼 Budgets',            'callback_data' => 'budget:list'],
                ],
                [
                    ['text' => '👥 Friends',           'callback_data' => 'friend:list'],
                    ['text' => '💰 Balances',          'callback_data' => 'friend:list'],
                ],
                [
                    ['text' => '🔄 Recurring',         'callback_data' => 'settings:recurring'],
                    ['text' => '🌐 Language',           'callback_data' => 'settings:language'],
                ],
                [
                    ['text' => '❤️ Health Score',      'callback_data' => 'settings:health'],
                    ['text' => '💡 Daily Insights',    'callback_data' => 'settings:insights'],
                ],
                [
                    ['text' => '🏋️ Financial Coach',  'callback_data' => 'settings:coach'],
                    ['text' => '🤖 AI Chat',           'callback_data' => 'ai:start_chat'],
                ],
                [
                    ['text' => '🔄 Subscriptions',    'callback_data' => 'ai:subscriptions'],
                    ['text' => '⚙️ Settings',          'callback_data' => 'settings:menu'],
                ],
            ],
        ];
    }
}
