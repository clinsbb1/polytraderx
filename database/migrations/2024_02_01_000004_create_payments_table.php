<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('nowpayments_id')->nullable();
            $table->decimal('amount_usd', 8, 2);
            $table->decimal('amount_crypto', 12, 6)->nullable();
            $table->string('currency')->nullable();
            $table->enum('status', ['pending', 'confirming', 'confirmed', 'finished', 'failed', 'expired', 'refunded']);
            $table->text('payment_url')->nullable();
            $table->json('ipn_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('nowpayments_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
