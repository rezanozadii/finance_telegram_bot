<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\ForecastingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function __construct(private ForecastingService $forecasting) {}

    public function show(Request $request): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $currency = $request->query('currency', $user->default_currency ?? 'USD');

        return response()->json($this->forecasting->forecast($user, $currency));
    }
}
