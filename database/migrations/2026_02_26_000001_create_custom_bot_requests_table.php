<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_bot_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('contact')->nullable();
            $table->text('strategy_summary');
            $table->string('markets')->nullable();
            $table->string('timeframe')->nullable();
            $table->json('risk_limits_json')->nullable();
            $table->boolean('wants_ai')->default(false);
            $table->string('budget_range')->nullable();
            $table->string('timeline')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'accepted', 'declined'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_bot_requests');
    }
};
