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
        $goals = $user->goals()->get()->map(fn ($g) => $this->format($g))->values();

        return response()->json($goals);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0.01|max:999999999',
            'currency'      => 'required|string|size:3',
            'deadline'      => 'nullable|date|after:today',
        ]);

        $goal = UserGoal::create([
            'user_id'        => $user->id,
            'name'           => $validated['name'],
            'target_amount'  => $validated['target_amount'],
            'current_amount' => 0,
            'currency'       => strtoupper($validated['currency']),
            'deadline'       => $validated['deadline'] ?? null,
            'status'         => 'active',
        ]);

        return response()->json($this->format($goal), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        $goal = $user->goals()->findOrFail($id);

        $validated = $request->validate([
            'current_amount' => 'sometimes|numeric|min:0|max:999999999',
            'status'         => 'sometimes|in:active,completed,paused',
            'notes'          => 'sometimes|nullable|string|max:1000',
        ]);

        $goal->update($validated);

        return response()->json($this->format($goal->fresh()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        $goal = $user->goals()->findOrFail($id);
        $goal->delete();

        return response()->json(['success' => true]);
    }

    private function format(UserGoal $g): array
    {
        return [
            'id'             => $g->id,
            'name'           => $g->name,
            'target_amount'  => (float) $g->target_amount,
            'current_amount' => (float) $g->current_amount,
            'remaining'      => $g->remaining(),
            'progress_pct'   => $g->progressPct(),
            'currency'       => $g->currency,
            'deadline'       => $g->deadline?->toDateString(),
            'status'         => $g->status,
        ];
    }
}
