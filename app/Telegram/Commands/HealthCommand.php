<?php

namespace App\Telegram\Commands;

use App\AI\Agents\FinancialHealthAgent;
use App\Models\User;
use App\Services\AI\HealthScoreService;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class HealthCommand extends Command
{
    protected string $name        = 'health';
    protected string $description = 'View your financial health score';

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
            'text'    => $lang === 'fa' ? '⏳ در حال محاسبه امتیاز سلامت مالی...' : '⏳ Calculating your financial health score...',
        ]);

        $score = app(HealthScoreService::class)->calculate($user, $currency);
        $total = $score['total'] ?? ($score['score'] ?? 0);
        $bar   = $this->progressBar($total);

        $lines = [$lang === 'fa' ? "❤️ *امتیاز سلامت مالی*\n" : "❤️ *Financial Health Score*\n"];
        $lines[] = "*{$total}/100*";
        $lines[] = $bar;

        if (!empty($score['personality'])) {
            $lines[] = '';
            $lines[] = ($lang === 'fa' ? '🧠 شخصیت: ' : '🧠 Personality: ') . $score['personality'];
        }

        if (!empty($score['components'])) {
            $lines[] = '';
            foreach ($score['components'] as $c) {
                $label = is_array($c) ? ($c['label'] ?? '') : '';
                $s     = is_array($c) ? ($c['score'] ?? 0) : 0;
                if ($label) {
                    $lines[] = "• *{$label}*: {$s}/100";
                }
            }
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => implode("\n", $lines),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $lang === 'fa' ? '🏋️ دریافت مشاوره' : '🏋️ Get Coaching',    'callback_data' => 'settings:coach'],
                        ['text' => $lang === 'fa' ? '💡 بینش‌های امروز' : '💡 Daily Insights', 'callback_data' => 'settings:insights'],
                    ],
                ],
            ]),
        ]);

        $explanation = app(FinancialHealthAgent::class)->explainScore($user, $currency);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => $explanation,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function progressBar(int|float $score): string
    {
        $filled = (int) min(10, round($score / 10));
        $empty  = 10 - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty) . " {$score}%";
    }
}
