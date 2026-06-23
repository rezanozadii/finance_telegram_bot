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

        // Key the raw Budget models by id so we can look up the category object
        $models = Budget::where('user_id', $user->id)
            ->with('category')
            ->get()
            ->keyBy('id');

        // Normalise to match the TypeScript Budget interface:
        // 'spent' → 'spent_amount', category string → {id, name} object
        $payload = array_map(function (array $b) use ($models): array {
            $model = $models->get($b['id']);
            $cat   = $model?->category;
            return [
                'id'           => $b['id'],
                'name'         => $b['name'],
                'amount'       => $b['amount'],
                'spent_amount' => $b['spent'],
                'currency'     => $b['currency'],
                'period'       => $b['period'],
                'pct_used'     => $b['pct_used'],
                'status'       => $b['status'],
                'category'     => $cat ? ['id' => $cat->id, 'name' => $cat->name] : null,
            ];
        }, $budgets);

        return response()->json(array_values($payload));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01|max:999999999',
            'currency'    => 'required|string|size:3',
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

        $budget->load('category');

        return response()->json([
            'id'           => $budget->id,
            'name'         => $budget->name,
            'amount'       => (float) $budget->amount,
            'spent_amount' => 0.0,
            'currency'     => $budget->currency,
            'period'       => $budget->period,
            'pct_used'     => 0.0,
            'status'       => 'safe',
            'category'     => $budget->category
                ? ['id' => $budget->category->id, 'name' => $budget->category->name]
                : null,
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user   = $request->attributes->get('telegram_user');
        $budget = $user->budgets()->findOrFail($id);
        $budget->delete();

        return response()->json(['success' => true]);
    }
}
