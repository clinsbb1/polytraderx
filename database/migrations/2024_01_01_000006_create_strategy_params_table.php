<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_params', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->enum('type', ['number', 'decimal', 'boolean', 'string', 'json']);
            $table->string('description');
            $table->string('group');
            $table->enum('updated_by', ['system', 'ai_audit', 'admin']);
            $table->string('previous_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_params');
    }
};
