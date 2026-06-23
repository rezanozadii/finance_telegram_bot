<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $expense = [
            ['name' => 'Food & Dining',  'name_fa' => 'غذا و رستوران',    'icon' => '🍔'],
            ['name' => 'Transport',      'name_fa' => 'حمل‌ونقل',         'icon' => '🚗'],
            ['name' => 'Housing',        'name_fa' => 'مسکن',              'icon' => '🏠'],
            ['name' => 'Health',         'name_fa' => 'سلامت',             'icon' => '💊'],
            ['name' => 'Entertainment',  'name_fa' => 'سرگرمی',            'icon' => '🎬'],
            ['name' => 'Shopping',       'name_fa' => 'خرید',              'icon' => '🛍️'],
            ['name' => 'Education',      'name_fa' => 'آموزش',             'icon' => '📚'],
            ['name' => 'Utilities',      'name_fa' => 'قبوض',              'icon' => '💡'],
            ['name' => 'Other',          'name_fa' => 'سایر',              'icon' => '📦'],
        ];

        $income = [
            ['name' => 'Salary',         'name_fa' => 'حقوق',             'icon' => '💼'],
            ['name' => 'Freelance',      'name_fa' => 'فریلنسر',          'icon' => '💻'],
            ['name' => 'Investment',     'name_fa' => 'سرمایه‌گذاری',     'icon' => '📈'],
            ['name' => 'Gift',           'name_fa' => 'هدیه',             'icon' => '🎁'],
            ['name' => 'Other Income',   'name_fa' => 'سایر درآمدها',    'icon' => '💰'],
        ];

        foreach ($expense as $cat) {
            Category::updateOrCreate(
                ['user_id' => null, 'name' => $cat['name'], 'type' => 'expense'],
                ['icon' => $cat['icon'], 'name_fa' => $cat['name_fa']]
            );
        }

        foreach ($income as $cat) {
            Category::updateOrCreate(
                ['user_id' => null, 'name' => $cat['name'], 'type' => 'income'],
                ['icon' => $cat['icon'], 'name_fa' => $cat['name_fa']]
            );
        }
    }
}
