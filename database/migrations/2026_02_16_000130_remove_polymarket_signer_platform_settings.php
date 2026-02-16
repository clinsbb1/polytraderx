<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_settings')
            ->whereIn('key', [
                'POLYMARKET_SIGNER_URL',
                'POLYMARKET_SIGNER_API_KEY',
                'POLYMARKET_SIGNER_TIMEOUT_SECONDS',
            ])
            ->delete();
    }

    public function down(): void
    {
        $now = now();

        $rows = [
            [
                'key' => 'POLYMARKET_SIGNER_URL',
                'value' => '',
                'type' => 'string',
                'group' => 'infrastructure',
                'description' => 'Internal service URL for EIP-712 order signing',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'POLYMARKET_SIGNER_API_KEY',
                'value' => '',
                'type' => 'string',
                'group' => 'infrastructure',
                'description' => 'Bearer token used when calling the signer service',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'POLYMARKET_SIGNER_TIMEOUT_SECONDS',
                'value' => '10',
                'type' => 'number',
                'group' => 'infrastructure',
                'description' => 'Signer service request timeout (seconds)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $row['key']],
                $row
            );
        }
    }
};

