<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('cycle_id', 64)->nullable()->index();
            $table->string('event', 50)->index();
            $table->string('market_id')->nullable()->index();
            $table->string('asset', 16)->nullable()->index();
            $table->boolean('matched_strategy')->nullable()->index();
            $table->string('action', 40)->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_activity_logs');
    }
};

