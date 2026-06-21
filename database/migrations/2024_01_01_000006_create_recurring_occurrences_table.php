<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('recurring_templates')->cascadeOnDelete();
            $table->date('due_date');
            $table->enum('status', ['pending', 'confirmed', 'skipped'])->default('pending');
            $table->foreignId('confirmed_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_occurrences');
    }
};
