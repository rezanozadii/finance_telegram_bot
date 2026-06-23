<?php

namespace App\Telegram\Commands;

use App\AI\Agents\FinancialCoachAgent;
use App\Models\User;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class CoachCommand extends Command
{
    protected string $name        = 'coach';
    protected string $description = 'Get AI financial coaching';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $chatId   = $this->getUpdate()->getMessage()->getChat()->getId();
        $lang     = $user->language ?? 'en';
        $currency = $user->default_currency ?? 'USD';

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $lang === 'fa' ? '⏳ در حال آماده کردن مشاوره مالی...' : '⏳ Preparing your financial coaching...',
        ]);

        $coaching = app(FinancialCoachAgent::class)->weeklyCoaching($user, $currency);

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => ($lang === 'fa' ? "🏋️ *مشاوره مالی*\n\n" : "🏋️ *Financial Coaching*\n\n") . $coaching,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => $lang === 'fa' ? '❤️ سلامت مالی' : '❤️ Health Score',   'callback_data' => 'settings:health'],
                    ['text' => $lang === 'fa' ? '💡 بینش‌های امروز' : '💡 Insights',    'callback_data' => 'settings:insights'],
                ]],
            ]),
        ]);
    }
}
