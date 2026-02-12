<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BalanceSnapshot;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SnapshotBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'trial_ends_at' => now()->addDays(7),
            'subscription_plan' => 'free_trial',
        ]);
        UserCredential::create([
            'user_id' => $user->id,
            'polymarket_api_key' => 'test-key',
            'polymarket_api_secret' => base64_encode('test-secret'),
            'polymarket_api_passphrase' => 'test-pass',
            'polymarket_wallet_address' => '0x' . str_repeat('a', 40),
        ]);
        return $user;
    }

    public function test_creates_balance_snapshot(): void
    {
        Http::fake([
            '*/time' => Http::response(['time' => time()], 200),
            '*' => Http::response([], 200),
        ]);

        $user = $this->makeUser();

        $this->artisan('bot:snapshot-balance')->assertSuccessful();

        $snapshot = BalanceSnapshot::forUser($user->id)->latest('snapshot_at')->first();
        $this->assertNotNull($snapshot);
        $this->assertEquals($user->id, $snapshot->user_id);
        $this->assertNotNull($snapshot->snapshot_at);
    }

    public function test_dry_run_calculates_from_trades(): void
    {
        Http::fake([
            '*/time' => Http::response(['time' => time()], 200),
            '*' => Http::response([], 200),
        ]);

        $user = $this->makeUser();

        // DRY_RUN is default true, so balance is simulated
        $this->artisan('bot:snapshot-balance')->assertSuccessful();

        $snapshot = BalanceSnapshot::forUser($user->id)->latest('snapshot_at')->first();
        $this->assertNotNull($snapshot);
        // Default initial balance is 100, no trades so equity = 100
        $this->assertEquals(100.00, (float) $snapshot->total_equity);
    }
}
