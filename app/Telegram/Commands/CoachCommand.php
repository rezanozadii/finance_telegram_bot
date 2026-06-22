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

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user->language === 'fa' ? '⏳ در حال آماده کردن مشاوره مالی...' : '⏳ Preparing your financial coaching...',
        ]);

        $currency = $user->default_currency ?? 'USD';
        $coaching = app(FinancialCoachAgent::class)->weeklyCoaching($user, $currency);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => "🏋️ *Financial Coaching*\n\n" . $coaching,
            'parse_mode' => 'Markdown',
        ]);
    }
}
