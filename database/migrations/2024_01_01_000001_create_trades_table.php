<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('market_id');
            $table->string('market_slug');
            $table->string('market_question');
            $table->string('asset');
            $table->enum('side', ['YES', 'NO']);
            $table->decimal('entry_price', 8, 4);
            $table->decimal('exit_price', 8, 4)->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('potential_payout', 10, 2);
            $table->enum('status', ['pending', 'open', 'won', 'lost', 'cancelled']);
            $table->decimal('confidence_score', 5, 4);
            $table->enum('decision_tier', ['reflexes', 'muscles', 'brain']);
            $table->json('decision_reasoning');
            $table->decimal('external_spot_at_entry', 12, 2)->nullable();
            $table->decimal('external_spot_at_resolution', 12, 2)->nullable();
            $table->timestamp('market_end_time')->nullable();
            $table->timestamp('entry_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->decimal('pnl', 10, 2)->nullable();
            $table->boolean('audited')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('asset');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
