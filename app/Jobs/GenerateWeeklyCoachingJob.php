<?php

namespace App\Jobs;

use App\AI\Agents\FinancialCoachAgent;
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

class GenerateWeeklyCoachingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(FinancialCoachAgent $agent): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $currency = $user->default_currency ?? 'USD';

        try {
            $content = $agent->weeklyCoaching($user, $currency);

            $insight = AiInsight::create([
                'user_id'       => $user->id,
                'type'          => 'weekly',
                'content'       => $content,
                'insights_date' => Carbon::today()->toDateString(),
                'is_sent'       => false,
            ]);

            if ($user->telegram_id) {
                Telegram::sendMessage([
                    'chat_id'    => $user->telegram_id,
                    'text'       => "🏋️ *Weekly Financial Coaching*\n\n" . $content,
                    'parse_mode' => 'Markdown',
                ]);

                $insight->update(['is_sent' => true]);
            }

        } catch (\Throwable $e) {
            Log::error('GenerateWeeklyCoachingJob failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
