<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('trades')->cascadeOnDelete();
            $table->string('event');
            $table->json('data');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_logs');
    }
};
