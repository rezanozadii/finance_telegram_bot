<?php

namespace App\Telegram\Commands;

use App\Services\AccountService;
use App\Services\UserService;
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
        $name     = $user->display_name;

        App::setLocale($lang);

        $isNew = $accounts->isEmpty();

        // Always send the persistent bottom keyboard first so buttons appear immediately
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $lang === 'fa'
                ? ($isNew ? "👋 خوش آمدی *{$name}*! من دستیار مالی شما هستم." : "👋 خوش برگشتی *{$name}*!")
                : ($isNew ? "👋 Welcome, *{$name}*! I'm your personal finance assistant." : "👋 Welcome back, *{$name}*!"),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(MainKeyboard::main($lang)),
        ]);

        // Always send the full feature inline menu
        $menuText = $lang === 'fa'
            ? "💼 از منوی زیر یک بخش انتخاب کنید:"
            : "💼 Choose a section — all features are one tap away:";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $menuText,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(self::fullMenu($lang)),
        ]);

        // For new users, additionally guide them to create their first account
        if ($isNew) {
            $hint = $lang === 'fa'
                ? "🏦 برای شروع، ابتدا یک حساب بسازید.\nروی *🏦 حساب‌ها* بزنید یا /accounts را ارسال کنید."
                : "🏦 To get started, create your first account.\nTap *🏦 Accounts* above or send /accounts.";

            Telegram::sendMessage([
                'chat_id'    => $chatId,
                'text'       => $hint,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => $lang === 'fa' ? '🏦 ایجاد حساب' : '🏦 Create Account', 'callback_data' => 'account:add'],
                    ]],
                ]),
            ]);
        }
    }

    public static function fullMenu(string $lang = 'en'): array
    {
        if ($lang === 'fa') {
            return [
                'inline_keyboard' => [
                    [
                        ['text' => '➕ افزودن تراکنش',     'callback_data' => 'txn:start'],
                        ['text' => '📋 تراکنش‌ها',         'callback_data' => 'txn_filter:all'],
                    ],
                    [
                        ['text' => '🏦 حساب‌ها',           'callback_data' => 'account:list'],
                        ['text' => '📂 دسته‌بندی‌ها',      'callback_data' => 'settings:categories'],
                    ],
                    [
                        ['text' => '📊 گزارش ماهانه',      'callback_data' => 'report:month'],
                        ['text' => '📈 گزارش سالانه',      'callback_data' => 'report:year'],
                    ],
                    [
                        ['text' => '🎯 اهداف',             'callback_data' => 'goal:list'],
                        ['text' => '💼 بودجه‌ها',          'callback_data' => 'budget:list'],
                    ],
                    [
                        ['text' => '👥 دوستان',            'callback_data' => 'friend:list'],
                        ['text' => '🔄 تراکنش‌های تکرار',  'callback_data' => 'settings:recurring'],
                    ],
                    [
                        ['text' => '❤️ سلامت مالی',        'callback_data' => 'settings:health'],
                        ['text' => '💡 بینش‌های روزانه',   'callback_data' => 'settings:insights'],
                    ],
                    [
                        ['text' => '🏋️ مشاوره مالی',      'callback_data' => 'settings:coach'],
                        ['text' => '🤖 چت هوش مصنوعی',    'callback_data' => 'ai:start_chat'],
                    ],
                    [
                        ['text' => '🔄 اشتراک‌ها',         'callback_data' => 'ai:subscriptions'],
                        ['text' => '🌐 زبان',              'callback_data' => 'settings:language'],
                    ],
                ],
            ];
        }

        return [
            'inline_keyboard' => [
                [
                    ['text' => '➕ Add Transaction',    'callback_data' => 'txn:start'],
                    ['text' => '📋 Transactions',       'callback_data' => 'txn_filter:all'],
                ],
                [
                    ['text' => '🏦 Accounts',           'callback_data' => 'account:list'],
                    ['text' => '📂 Categories',          'callback_data' => 'settings:categories'],
                ],
                [
                    ['text' => '📊 Monthly Report',     'callback_data' => 'report:month'],
                    ['text' => '📈 Yearly Report',      'callback_data' => 'report:year'],
                ],
                [
                    ['text' => '🎯 Goals',              'callback_data' => 'goal:list'],
                    ['text' => '💼 Budgets',             'callback_data' => 'budget:list'],
                ],
                [
                    ['text' => '👥 Friends',            'callback_data' => 'friend:list'],
                    ['text' => '🔄 Recurring',          'callback_data' => 'settings:recurring'],
                ],
                [
                    ['text' => '❤️ Health Score',       'callback_data' => 'settings:health'],
                    ['text' => '💡 Daily Insights',     'callback_data' => 'settings:insights'],
                ],
                [
                    ['text' => '🏋️ Financial Coach',   'callback_data' => 'settings:coach'],
                    ['text' => '🤖 AI Chat',            'callback_data' => 'ai:start_chat'],
                ],
                [
                    ['text' => '🔄 Subscriptions',     'callback_data' => 'ai:subscriptions'],
                    ['text' => '🌐 Language',           'callback_data' => 'settings:language'],
                ],
            ],
        ];
    }
}
