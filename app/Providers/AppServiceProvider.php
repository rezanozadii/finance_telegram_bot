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

    public function boot(): void {}
}
