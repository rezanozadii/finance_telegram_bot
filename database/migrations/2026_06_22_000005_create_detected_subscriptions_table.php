<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detected_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('merchant');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10);
            $table->enum('frequency', ['monthly', 'yearly', 'weekly'])->default('monthly');
            $table->date('last_payment_at')->nullable();
            $table->date('next_predicted_at')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detected_subscriptions');
    }
};
