<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailySummary;
use App\Models\Trade;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DailySummaryCommandTest extends TestCase
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

    public function test_creates_daily_summary_with_correct_stats(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = $this->makeUser();
        $yesterday = now()->subDay()->toDateString();

        // Create trades resolved yesterday
        Trade::factory()->won()->create([
            'user_id' => $user->id,
            'pnl' => 5.00,
            'resolved_at' => now()->subDay(),
        ]);
        Trade::factory()->won()->create([
            'user_id' => $user->id,
            'pnl' => 3.00,
            'resolved_at' => now()->subDay(),
        ]);
        Trade::factory()->lost()->create([
            'user_id' => $user->id,
            'pnl' => -2.00,
            'resolved_at' => now()->subDay(),
        ]);

        $this->artisan('bot:daily-summary')->assertSuccessful();

        $summary = DailySummary::forUser($user->id)->whereDate('date', $yesterday)->first();
        $this->assertNotNull($summary);
        $this->assertEquals(3, $summary->total_trades);
        $this->assertEquals(2, $summary->wins);
        $this->assertEquals(1, $summary->losses);
        $this->assertEquals(6.00, (float) $summary->gross_pnl);
    }

    public function test_handles_user_with_no_trades(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = $this->makeUser();
        $yesterday = now()->subDay()->toDateString();

        $this->artisan('bot:daily-summary')->assertSuccessful();

        $summary = DailySummary::forUser($user->id)->whereDate('date', $yesterday)->first();
        $this->assertNotNull($summary);
        $this->assertEquals(0, $summary->total_trades);
        $this->assertEquals(0, $summary->wins);
        $this->assertEquals(0, $summary->losses);
        $this->assertEquals(0, (float) $summary->gross_pnl);
    }
}
