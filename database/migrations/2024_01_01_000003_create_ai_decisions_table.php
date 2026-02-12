<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->nullable()->constrained('trades')->cascadeOnDelete();
            $table->enum('tier', ['muscles', 'brain']);
            $table->string('model_used');
            $table->longText('prompt')->default('');
            $table->longText('response')->default('');
            $table->integer('tokens_input');
            $table->integer('tokens_output');
            $table->decimal('cost_usd', 8, 6);
            $table->string('decision_type');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_decisions');
    }
};
