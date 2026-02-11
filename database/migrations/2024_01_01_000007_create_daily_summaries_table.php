<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('total_trades');
            $table->integer('wins');
            $table->integer('losses');
            $table->decimal('win_rate', 5, 2);
            $table->decimal('gross_pnl', 10, 2);
            $table->decimal('net_pnl', 10, 2);
            $table->decimal('ai_cost_usd', 8, 4);
            $table->foreignId('best_trade_id')->nullable()->constrained('trades')->nullOnDelete();
            $table->foreignId('worst_trade_id')->nullable()->constrained('trades')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
