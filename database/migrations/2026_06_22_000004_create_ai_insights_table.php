<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['daily', 'weekly', 'monthly']);
            $table->text('content');
            $table->date('insights_date');
            $table->boolean('is_sent')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'insights_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
