<?php

namespace App\Providers;

use App\AI\AgentOrchestrator;
use App\Services\AI\AiClientService;
use App\Services\AI\AiMemoryService;
use App\Services\AI\BudgetAnalysisService;
use App\Services\AI\FinancialCalculatorService;
use App\Services\AI\ForecastingService;
use App\Services\AI\HabitDetectorService;
use App\Services\AI\HealthScoreService;
use App\Services\AI\SpendingPatternService;
use App\Services\AI\SubscriptionDetectorService;
use App\Services\AI\WhatIfSimulatorService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiClientService::class);
        $this->app->singleton(FinancialCalculatorService::class);
        $this->app->singleton(BudgetAnalysisService::class);
        $this->app->singleton(HealthScoreService::class);
        $this->app->singleton(SpendingPatternService::class);
        $this->app->singleton(ForecastingService::class);
        $this->app->singleton(SubscriptionDetectorService::class);
        $this->app->singleton(HabitDetectorService::class);
        $this->app->singleton(WhatIfSimulatorService::class);
        $this->app->singleton(AiMemoryService::class);
        $this->app->singleton(AgentOrchestrator::class);
    }

    public function boot(): void
    {
        // General API: 60 requests per minute, keyed by authenticated user id
        RateLimiter::for('api', function (Request $request) {
            $user = $request->attributes->get('telegram_user');
            $key  = $user ? 'user:' . $user->id : 'ip:' . $request->ip();
            return Limit::perMinute(60)->by($key)->response(
                fn () => response()->json(['error' => 'Too many requests. Please slow down.'], 429)
            );
        });

        // AI endpoints: expensive — limit to 15 per minute per user
        RateLimiter::for('api.ai', function (Request $request) {
            $user = $request->attributes->get('telegram_user');
            $key  = $user ? 'ai:user:' . $user->id : 'ai:ip:' . $request->ip();
            return Limit::perMinute(15)->by($key)->response(
                fn () => response()->json(['error' => 'AI rate limit reached. Please wait a moment.'], 429)
            );
        });

        // Telegram webhook: limit by IP to protect against replay floods
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip())->response(
                fn () => response()->json(['error' => 'Too many requests'], 429)
            );
        });
    }
}
