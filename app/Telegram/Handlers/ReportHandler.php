<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\ReportService;
use App\Telegram\Keyboards\ReportKeyboard;
use Carbon\Carbon;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;

class ReportHandler
{
    public function __construct(private ReportService $reportService) {}

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        // action format: "report:month" | "report:last_month" etc.
        $type = substr($action, 7); // strip "report:"

        $this->show($telegramId, $chatId, $type, $messageId);
    }

    public function show(
        int|string $telegramId,
        int|string $chatId,
        string     $periodType = 'month',
        ?int       $messageId  = null,
        ?string    $monthParam = null,
    ): void {
        $user = User::where('telegram_id', $telegramId)->firstOrFail();
        $currency = $user->default_currency ?? 'USD';

        // Resolve period bounds
        if ($periodType === 'month' && $monthParam) {
            [$start, $end] = $this->reportService->periodBounds('month', $monthParam);
        } elseif (in_array($periodType, ['month', 'last_month', 'quarter', 'year'])) {
            [$start, $end] = $this->reportService->periodBounds($periodType);
        } else {
            // custom: periodType is "YYYY-MM-DD YYYY-MM-DD"
            $dates = explode(' ', $periodType, 2);
            [$start, $end] = [
                Carbon::parse($dates[0])->startOfDay(),
                Carbon::parse($dates[1])->endOfDay(),
            ];
        }

        $data     = $this->reportService->generate($user, $start, $end, $currency);
        $prevType = in_array($periodType, ['month', 'last_month', 'quarter', 'year']) ? $periodType : 'custom';

        [$prevStart, $prevEnd] = $this->reportService->previousPeriodBounds($prevType, $start, $end);
        $prevData = $this->reportService->generate($user, $prevStart, $prevEnd, $currency);

        $label     = $this->reportService->periodLabel($periodType, $start, $end);
        $prevLabel = $this->reportService->periodLabel($prevType, $prevStart, $prevEnd);

        $text = $this->buildText($data, $prevData, $label, $prevLabel, $currency);

        $isNamedPeriod = in_array($periodType, ['month', 'last_month', 'quarter', 'year']);
        $keyboard      = $isNamedPeriod ? ReportKeyboard::periodNav($periodType) : null;

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ];
        if ($keyboard) {
            $payload['reply_markup'] = json_encode($keyboard);
        }

        $messageId
            ? Telegram::editMessageText(array_merge($payload, ['message_id' => $messageId]))
            : Telegram::sendMessage($payload);
    }

    private function buildText(array $d, array $p, string $label, string $prevLabel, string $currency): string
    {
        $fmt = fn (float $v) => number_format($v, 2);
        $net = $d['net'];

        $lines = ["📊 *{$label}*\n"];
        $lines[] = "💰 " . __('bot.report_income')   . "     {$currency} " . $fmt($d['income']);
        $lines[] = "💸 " . __('bot.report_expenses')  . "   {$currency} " . $fmt($d['expenses']);
        $lines[] = "💵 " . __('bot.report_net')       . "        " . ($net >= 0 ? '+' : '') . $fmt($net);
        $lines[] = __('bot.report_transactions', ['count' => $d['count']]);

        if (!empty($d['other_currencies'])) {
            $others  = implode(', ', $d['other_currencies']);
            $lines[] = __('bot.report_other_currencies', ['currency' => $currency, 'others' => $others]);
        }

        if (!empty($d['by_category'])) {
            $lines[] = "\n─────────────────────";
            $lines[] = "*" . __('bot.report_by_category') . "*\n";

            foreach ($d['by_category'] as $cat) {
                $icon  = $cat['icon'] ?? '📦';
                $name  = $cat['name'];
                $pct   = number_format($cat['pct'], 1) . '%';
                $amt   = $fmt($cat['amount']);
                // Pad so amounts align
                $lines[] = "{$icon} {$name}  {$pct}  {$currency} {$amt}";
            }
        }

        // Comparison to previous period
        $lines[] = "\n─────────────────────";
        $lines[] = "*" . __('bot.report_vs', ['period' => $prevLabel]) . "*\n";

        $incomeChange  = $this->reportService->formatChange($d['income'], $p['income']);
        $expenseChange = $this->reportService->formatChange($d['expenses'], $p['expenses']);

        $lines[] = "Income:   {$currency} " . $fmt($p['income']) . "  {$incomeChange}";
        $lines[] = "Expenses: {$currency} " . $fmt($p['expenses']) . "  {$expenseChange}";

        return implode("\n", $lines);
    }
}
