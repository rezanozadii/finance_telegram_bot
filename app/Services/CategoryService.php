<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function create(
        User $user,
        string $name,
        string $type,
        ?string $icon = null,
        ?int $parentId = null,
    ): Category {
        return Category::create([
            'user_id'   => $user->id,
            'name'      => $name,
            'type'      => $type,
            'icon'      => $icon,
            'parent_id' => $parentId,
        ]);
    }

    public function listForUser(User $user): Collection
    {
        return $user->categories()->with('parent')->orderBy('type')->orderBy('name')->get();
    }

    /** Top-level categories of a given type — used for parent selection. */
    public function topLevel(User $user, string $type): Collection
    {
        return $user->categories()
            ->where('type', $type)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function rename(Category $category, string $name): void
    {
        $category->update(['name' => $name]);
    }

    public function changeIcon(Category $category, string $icon): void
    {
        $category->update(['icon' => $icon]);
    }

    /**
     * Delete only if no transactions reference this category.
     * Children become top-level via DB nullOnDelete cascade.
     */
    public function delete(Category $category): bool
    {
        if ($category->transactions()->exists()) {
            return false;
        }

        $category->delete();
        return true;
    }

    public function canDelete(Category $category): bool
    {
        return !$category->transactions()->exists();
    }
}
