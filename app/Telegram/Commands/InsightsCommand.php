<?php

namespace App\Telegram\Commands;

use App\Models\AiInsight;
use App\Models\User;
use Carbon\Carbon;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class InsightsCommand extends Command
{
    protected string $name        = 'insights';
    protected string $description = 'View today\'s AI insights';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $chatId  = $this->getUpdate()->getMessage()->getChat()->getId();
        $lang    = $user->language ?? 'en';
        $insight = AiInsight::where('user_id', $user->id)
            ->whereDate('insights_date', Carbon::today())
            ->latest()
            ->first();

        if (!$insight) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => $lang === 'fa'
                    ? '💡 امروز هنوز هیچ بینشی تولید نشده. فردا صبح دوباره بررسی کنید.'
                    : '💡 No insights generated yet for today. Check back tomorrow morning.',
            ]);
            return;
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => ($lang === 'fa' ? "💡 *بینش‌های روزانه*\n\n" : "💡 *Daily Insights*\n\n") . $insight->content,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => $lang === 'fa' ? '❤️ سلامت مالی' : '❤️ Health Score', 'callback_data' => 'settings:health'],
                    ['text' => $lang === 'fa' ? '🏋️ مشاوره' : '🏋️ Coaching',        'callback_data' => 'settings:coach'],
                ]],
            ]),
        ]);
    }
}
