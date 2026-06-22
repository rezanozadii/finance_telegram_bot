<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->attributes->get('telegram_user');
        $goals = $user->goals()->get()->map(fn ($g) => [
            'id'             => $g->id,
            'name'           => $g->name,
            'target_amount'  => (float) $g->target_amount,
            'current_amount' => (float) $g->current_amount,
            'remaining'      => $g->remaining(),
            'progress_pct'   => $g->progressPct(),
            'currency'       => $g->currency,
            'deadline'       => $g->deadline?->toDateString(),
            'status'         => $g->status,
        ]);

        return response()->json(['goals' => $goals]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0.01',
            'currency'      => 'required|string|max:10',
            'deadline'      => 'nullable|date',
        ]);

        $goal = UserGoal::create([
            'user_id'       => $user->id,
            'name'          => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount'=> 0,
            'currency'      => strtoupper($validated['currency']),
            'deadline'      => $validated['deadline'] ?? null,
            'status'        => 'active',
        ]);

        return response()->json(['goal' => $goal], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        $goal = $user->goals()->findOrFail($id);

        $goal->update($request->only(['current_amount', 'status', 'notes']));

        return response()->json(['goal' => $goal]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        $goal = $user->goals()->findOrFail($id);
        $goal->delete();

        return response()->json(['success' => true]);
    }
}
