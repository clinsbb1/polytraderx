<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Fetch all user IDs that already have a SCAN_MODE param
        $alreadySeeded = DB::table('strategy_params')
            ->where('key', 'SCAN_MODE')
            ->pluck('user_id')
            ->all();

        // Get all user IDs not yet seeded
        $userIds = DB::table('users')
            ->whereNotIn('id', $alreadySeeded)
            ->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('strategy_params')->insert([
                'user_id'     => $userId,
                'key'         => 'SCAN_MODE',
                'value'       => 'reflexes',
                'type'        => 'string',
                'description' => 'Scan engine: reflexes (System Scan) or muscles (AI Scan)',
                'group'       => 'trading',
                'updated_by'  => 'system',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('strategy_params')->where('key', 'SCAN_MODE')->delete();
    }
};
