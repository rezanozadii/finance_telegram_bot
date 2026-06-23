<?php

use App\Telegram\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['status' => 'ok']));

Route::post('/webhook/telegram', WebhookController::class)
    ->middleware('throttle:webhook')
    ->name('telegram.webhook');

// Serve the React Mini App — all /mini-app/* paths fall through to index.html
Route::get('/mini-app/{any?}', fn () => response()->file(public_path('mini-app/index.html')))
    ->where('any', '.*')
    ->name('mini-app');
