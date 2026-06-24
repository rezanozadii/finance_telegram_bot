<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ForecastController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['telegram.auth', 'throttle:api'])->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::patch('/me', [UserController::class, 'update']);

    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    Route::get('/report', [ReportController::class, 'show']);

    Route::get('/friends', [FriendController::class, 'index']);
    Route::get('/friends/{friendId}/expenses', [FriendController::class, 'expenses']);

    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::patch('/goals/{id}', [GoalController::class, 'update']);
    Route::delete('/goals/{id}', [GoalController::class, 'destroy']);

    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy']);

    Route::get('/forecast', [ForecastController::class, 'show']);

    // AI Financial Coach — tighter rate limit (expensive)
    Route::middleware('throttle:api.ai')->group(function () {
        Route::post('/ai/chat', [AiController::class, 'chat']);
        Route::get('/ai/insights', [AiController::class, 'insights']);
        Route::get('/ai/health-score', [AiController::class, 'healthScore']);
        Route::get('/ai/subscriptions', [AiController::class, 'subscriptions']);
        Route::get('/ai/habits', [AiController::class, 'habits']);
        Route::post('/ai/whatif', [AiController::class, 'whatIf']);
    });
});
