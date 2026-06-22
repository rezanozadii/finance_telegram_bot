<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Services\AI\BudgetAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private BudgetAnalysisService $analysis) {}

    public function index(Request $request): JsonResponse
    {
        $user    = $request->attributes->get('telegram_user');
        $budgets = $this->analysis->analyze($user);

        return response()->json(['budgets' => $budgets]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'required|string|max:10',
            'period'      => 'required|in:monthly,weekly,yearly',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $budget = Budget::create([
            'user_id'     => $user->id,
            'name'        => $validated['name'],
            'amount'      => $validated['amount'],
            'currency'    => strtoupper($validated['currency']),
            'period'      => $validated['period'],
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return response()->json(['budget' => $budget], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user   = $request->attributes->get('telegram_user');
        $budget = $user->budgets()->findOrFail($id);
        $budget->delete();

        return response()->json(['success' => true]);
    }
}
