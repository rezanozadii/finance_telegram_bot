<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('telegram.auth')->group(function () {
    Route::get('/me', [UserController::class, 'me']);

    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    Route::get('/report', [ReportController::class, 'show']);

    Route::get('/friends', [FriendController::class, 'index']);
    Route::get('/friends/{friendId}/expenses', [FriendController::class, 'expenses']);
});
