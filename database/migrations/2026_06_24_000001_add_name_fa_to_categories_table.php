<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_fa')->nullable()->after('name');
        });

        // Backfill Persian names for the default categories
        $map = [
            'Food & Dining'  => 'غذا و رستوران',
            'Transport'      => 'حمل‌ونقل',
            'Housing'        => 'مسکن',
            'Health'         => 'سلامت',
            'Entertainment'  => 'سرگرمی',
            'Shopping'       => 'خرید',
            'Education'      => 'آموزش',
            'Utilities'      => 'قبوض',
            'Other'          => 'سایر',
            'Salary'         => 'حقوق',
            'Freelance'      => 'فریلنسر',
            'Investment'     => 'سرمایه‌گذاری',
            'Gift'           => 'هدیه',
            'Other Income'   => 'سایر درآمدها',
        ];

        foreach ($map as $en => $fa) {
            DB::table('categories')
                ->whereNull('name_fa')
                ->where('name', $en)
                ->update(['name_fa' => $fa]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('name_fa');
        });
    }
};
