<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'name'      => $c->name,
            'type'      => $c->type,
            'icon'      => $c->icon,
            'parent_id' => $c->parent_id,
        ])->values());
    }
}
