<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance_usdc', 10, 2);
            $table->decimal('open_positions_value', 10, 2);
            $table->decimal('total_equity', 10, 2);
            $table->timestamp('snapshot_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_snapshots');
    }
};
