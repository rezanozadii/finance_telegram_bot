<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

class UserService
{
    public function findOrCreate(object $from): User
    {
        $user = User::firstOrCreate(
            ['telegram_id' => $from->getId()],
            [
                'username'     => $from->getUsername(),
                'display_name' => trim(($from->getFirstName() ?? '') . ' ' . ($from->getLastName() ?? '')),
                'default_currency' => 'USD',
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->seedUserCategories($user);
        }

        return $user;
    }

    private function seedUserCategories(User $user): void
    {
        // Copy system defaults into user's own category list so they can customise without touching globals
        Category::whereNull('user_id')->get()->each(function (Category $cat) use ($user) {
            Category::create([
                'user_id' => $user->id,
                'name'    => $cat->name,
                'type'    => $cat->type,
                'icon'    => $cat->icon,
            ]);
        });
    }
}
