<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        $type = $request->query('type'); // 'expense' | 'income' | null (all)

        $categories = $this->categoryService->listForUser($user);

        if ($type) {
            $categories = $categories->where('type', $type)->values();
        }

        return response()->json($categories->map(fn ($c) => [
            'id'        => $c->id,
            'name'      => $c->localizedName(),
            'type'      => $c->type,
            'icon'      => $c->icon,
            'parent_id' => $c->parent_id,
        ])->values());
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:80'],
            'type'      => ['required', Rule::in(['income', 'expense'])],
            'icon'      => ['nullable', 'string', 'max:10'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        // Validate parent belongs to this user and matches type
        if (!empty($data['parent_id'])) {
            $parent = $this->categoryService->listForUser($user)->find($data['parent_id']);
            if (!$parent || $parent->type !== $data['type']) {
                return response()->json(['message' => 'Invalid parent category.'], 422);
            }
        }

        $category = $this->categoryService->create(
            $user,
            $data['name'],
            $data['type'],
            $data['icon'] ?? null,
            $data['parent_id'] ?? null,
        );

        return response()->json([
            'id'        => $category->id,
            'name'      => $category->localizedName(),
            'type'      => $category->type,
            'icon'      => $category->icon,
            'parent_id' => $category->parent_id,
        ], 201);
    }
}
