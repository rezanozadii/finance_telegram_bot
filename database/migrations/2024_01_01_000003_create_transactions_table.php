<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('merchant')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('source', ['manual', 'ai_parsed'])->default('manual');
            $table->text('raw_input_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
