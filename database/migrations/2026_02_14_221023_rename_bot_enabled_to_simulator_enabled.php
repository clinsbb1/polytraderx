<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('strategy_params')
            ->where('key', 'BOT_ENABLED')
            ->update(['key' => 'SIMULATOR_ENABLED']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('strategy_params')
            ->where('key', 'SIMULATOR_ENABLED')
            ->update(['key' => 'BOT_ENABLED']);
    }
};
