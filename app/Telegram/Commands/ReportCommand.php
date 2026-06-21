<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Handlers\ReportHandler;
use Telegram\Bot\Commands\Command;

class ReportCommand extends Command
{
    protected string $name        = 'report';
    protected string $description = 'Expense/income report (month | quarter | year)';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        $text   = $this->getUpdate()->getMessage()->getText() ?? '';

        // Strip the command prefix: "/report quarter" → "quarter"
        $args = trim(substr($text, strlen('/report')));

        [$periodType, $monthParam] = $this->parseArgs($args);

        app(ReportHandler::class)->show($from->getId(), $chatId, $periodType, null, $monthParam);
    }

    private function parseArgs(string $args): array
    {
        if ($args === '') {
            return ['month', null];
        }

        $parts = preg_split('/\s+/', $args, 3);
        $first = strtolower($parts[0]);

        // "/report month 2026-05"
        if ($first === 'month' && isset($parts[1]) && preg_match('/^\d{4}-\d{2}$/', $parts[1])) {
            return ['month', $parts[1]];
        }

        // "/report month|quarter|year|last_month|last month"
        if (in_array($first, ['month', 'quarter', 'year'])) {
            return [$first, null];
        }

        if ($first === 'last' && ($parts[1] ?? '') === 'month') {
            return ['last_month', null];
        }
        if ($first === 'last_month') {
            return ['last_month', null];
        }

        // "/report 2026-01-01 2026-03-31"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $first) && isset($parts[1]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $parts[1])) {
            return [$first . ' ' . $parts[1], null];
        }

        return ['month', null];
    }
}
