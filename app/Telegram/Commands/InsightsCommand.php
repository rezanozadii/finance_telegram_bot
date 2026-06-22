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
        $insight = AiInsight::where('user_id', $user->id)
            ->whereDate('insights_date', Carbon::today())
            ->latest()
            ->first();

        if (!$insight) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => $user->language === 'fa'
                    ? '💡 امروز هنوز هیچ بینشی تولید نشده. فردا صبح دوباره بررسی کنید.'
                    : '💡 No insights generated yet for today. Check back tomorrow morning.',
            ]);
            return;
        }

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => "💡 *Daily Insights*\n\n" . $insight->content,
            'parse_mode' => 'Markdown',
        ]);
    }
}
