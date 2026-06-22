<?php

namespace App\Http\Controllers\Api;

use App\AI\AgentOrchestrator;
use App\Http\Controllers\Controller;
use App\Models\AiInsight;
use App\Services\AI\BudgetAnalysisService;
use App\Services\AI\HabitDetectorService;
use App\Services\AI\HealthScoreService;
use App\Services\AI\SubscriptionDetectorService;
use App\Services\AI\WhatIfSimulatorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function chat(
        Request $request,
        AgentOrchestrator $orchestrator,
    ): JsonResponse {
        $user     = $request->attributes->get('telegram_user');
        $message  = $request->input('message', '');
        $currency = $request->input('currency', $user->default_currency ?? 'USD');

        if (empty($message)) {
            return response()->json(['error' => 'Message is required'], 422);
        }

        $response = $orchestrator->handle($user, $message, $currency);

        return response()->json(['response' => $response]);
    }

    public function insights(Request $request): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $insights = AiInsight::where('user_id', $user->id)
            ->whereDate('insights_date', Carbon::today())
            ->orderByDesc('created_at')
            ->get(['type', 'content', 'insights_date', 'is_sent']);

        return response()->json(['insights' => $insights]);
    }

    public function healthScore(Request $request, HealthScoreService $service): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $currency = $request->query('currency', $user->default_currency ?? 'USD');

        return response()->json($service->calculate($user, $currency));
    }

    public function subscriptions(Request $request, SubscriptionDetectorService $service): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        return response()->json(['subscriptions' => $service->detect($user)]);
    }

    public function habits(Request $request, HabitDetectorService $service): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $currency = $request->query('currency', $user->default_currency ?? 'USD');

        return response()->json(['habits' => $service->detect($user, $currency)]);
    }

    public function whatIf(Request $request, WhatIfSimulatorService $service): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $scenario = $request->input('scenario', '');
        $params   = $request->input('params', []);
        $currency = $request->input('currency', $user->default_currency ?? 'USD');

        if (empty($scenario)) {
            return response()->json(['error' => 'Scenario is required'], 422);
        }

        return response()->json($service->simulate($user, $scenario, $params, $currency));
    }
}
