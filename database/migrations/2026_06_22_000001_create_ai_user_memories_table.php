<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('personality')->default('Balanced Saver');
            $table->string('preferred_currency')->default('USD');
            $table->tinyInteger('salary_day')->nullable();
            $table->string('risk_level')->default('Low');
            $table->decimal('saving_rate', 8, 2)->default(0);
            $table->string('largest_category')->nullable();
            $table->json('overspending_categories')->nullable();
            $table->json('goals_summary')->nullable();
            $table->text('last_summary')->nullable();
            $table->timestamp('profile_updated_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_memories');
    }
};
