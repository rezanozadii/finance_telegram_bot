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
        $currency = $user->default_currency ?? 'USD';

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user->language === 'fa' ? '⏳ در حال محاسبه امتیاز سلامت مالی...' : '⏳ Calculating your financial health score...',
        ]);

        $score  = app(HealthScoreService::class)->calculate($user, $currency);
        $header = $this->buildScoreHeader($score);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => $header,
            'parse_mode' => 'Markdown',
        ]);

        $explanation = app(FinancialHealthAgent::class)->explainScore($user, $currency);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => $explanation,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function buildScoreHeader(array $score): string
    {
        $totalScore = $score['score'];
        $grade      = $score['grade'];
        $bar        = $this->progressBar($totalScore);

        $lines = ["🏥 *Financial Health Score*\n"];
        $lines[] = "*{$totalScore}/100* (Grade: {$grade})";
        $lines[] = $bar;
        $lines[] = '';

        foreach ($score['components'] as $c) {
            $lines[] = "• *{$c['label']}*: {$c['score']}/100 (×{$c['weight']}%)";
        }

        return implode("\n", $lines);
    }

    private function progressBar(int $score): string
    {
        $filled = (int) round($score / 10);
        $empty  = 10 - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty) . " {$score}%";
    }
}
