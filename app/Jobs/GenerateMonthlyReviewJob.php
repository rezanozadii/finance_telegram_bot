<?php

namespace App\Jobs;

use App\AI\Agents\ReportWriterAgent;
use App\Models\AiInsight;
use App\Models\User;
use App\Services\AI\AiMemoryService;
use App\Services\AI\FinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class GenerateMonthlyReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(
        ReportWriterAgent $agent,
        AiMemoryService $memoryService,
        FinancialCalculatorService $calculator,
    ): void {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $currency    = $user->default_currency ?? 'USD';
        $lastMonth   = Carbon::now()->subMonth();
        $start       = $lastMonth->copy()->startOfMonth();
        $end         = $lastMonth->copy()->endOfMonth();

        try {
            $content = $agent->monthlyReport($user, $currency, $lastMonth);

            $insight = AiInsight::create([
                'user_id'       => $user->id,
                'type'          => 'monthly',
                'content'       => $content,
                'insights_date' => Carbon::today()->toDateString(),
                'is_sent'       => false,
            ]);

            $stats = $calculator->getStats($user, $start, $end, $currency);
            $memoryService->updateFromStats($user, $stats);

            if ($user->telegram_id) {
                Telegram::sendMessage([
                    'chat_id'    => $user->telegram_id,
                    'text'       => "📅 *Monthly Report — {$lastMonth->format('F Y')}*\n\n" . $content,
                    'parse_mode' => 'Markdown',
                ]);

                $insight->update(['is_sent' => true]);
            }

        } catch (\Throwable $e) {
            Log::error('GenerateMonthlyReviewJob failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
