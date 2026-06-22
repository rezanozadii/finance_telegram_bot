<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDailyInsightsJob;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateAiInsights extends Command
{
    protected $signature   = 'ai:daily-insights';
    protected $description = 'Generate daily AI insights for all users';

    public function handle(): void
    {
        User::all()->each(fn ($user) => GenerateDailyInsightsJob::dispatch($user->id));
        $this->info('Daily insights jobs dispatched.');
    }
}
