<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $expense = [
            ['name' => 'Food & Dining', 'icon' => '🍔'],
            ['name' => 'Transport', 'icon' => '🚗'],
            ['name' => 'Housing', 'icon' => '🏠'],
            ['name' => 'Health', 'icon' => '💊'],
            ['name' => 'Entertainment', 'icon' => '🎬'],
            ['name' => 'Shopping', 'icon' => '🛍️'],
            ['name' => 'Education', 'icon' => '📚'],
            ['name' => 'Utilities', 'icon' => '💡'],
            ['name' => 'Other', 'icon' => '📦'],
        ];

        $income = [
            ['name' => 'Salary', 'icon' => '💼'],
            ['name' => 'Freelance', 'icon' => '💻'],
            ['name' => 'Investment', 'icon' => '📈'],
            ['name' => 'Gift', 'icon' => '🎁'],
            ['name' => 'Other Income', 'icon' => '💰'],
        ];

        foreach ($expense as $cat) {
            Category::firstOrCreate(
                ['user_id' => null, 'name' => $cat['name'], 'type' => 'expense'],
                ['icon' => $cat['icon']]
            );
        }

        foreach ($income as $cat) {
            Category::firstOrCreate(
                ['user_id' => null, 'name' => $cat['name'], 'type' => 'income'],
                ['icon' => $cat['icon']]
            );
        }
    }
}
