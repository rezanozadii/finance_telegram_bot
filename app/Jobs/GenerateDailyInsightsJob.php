<?php

namespace App\Jobs;

use App\AI\Agents\SpendingAnalyzerAgent;
use App\Models\AiInsight;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class GenerateDailyInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(SpendingAnalyzerAgent $agent): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $currency = $user->default_currency ?? 'USD';
        $today    = Carbon::today();

        $alreadyGenerated = AiInsight::where('user_id', $user->id)
            ->where('insights_date', $today->toDateString())
            ->where('type', 'daily')
            ->exists();

        if ($alreadyGenerated) {
            return;
        }

        try {
            $content = $agent->analyze($user, $currency, Carbon::now()->startOfMonth(), Carbon::now());

            $insight = AiInsight::create([
                'user_id'       => $user->id,
                'type'          => 'daily',
                'content'       => $content,
                'insights_date' => $today->toDateString(),
                'is_sent'       => false,
            ]);

            if ($user->telegram_id) {
                Telegram::sendMessage([
                    'chat_id'    => $user->telegram_id,
                    'text'       => "💡 *Daily Insights*\n\n" . $content,
                    'parse_mode' => 'Markdown',
                ]);

                $insight->update(['is_sent' => true]);
            }

        } catch (\Throwable $e) {
            Log::error('GenerateDailyInsightsJob failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
