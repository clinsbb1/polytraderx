<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;
use App\Services\Trading\RiskManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeRiskManager(array $settingsOverrides = []): RiskManager
    {
        $defaults = [
            'SIMULATOR_ENABLED' => true,
            'MAX_DAILY_LOSS' => 50.0,
            'MAX_DAILY_TRADES' => 48,
            'MAX_CONCURRENT_POSITIONS' => 3,
            'MAX_BET_AMOUNT' => 10.0,
            'MAX_BET_PERCENTAGE' => 10.0,
        ];

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturnCallback(function ($key) use ($defaults, $settingsOverrides) {
            return (bool) ($settingsOverrides[$key] ?? $defaults[$key] ?? false);
        });
        $settings->method('getFloat')->willReturnCallback(function ($key) use ($defaults, $settingsOverrides) {
            return (float) ($settingsOverrides[$key] ?? $defaults[$key] ?? 0.0);
        });
        $settings->method('getInt')->willReturnCallback(function ($key) use ($defaults, $settingsOverrides) {
            return (int) ($settingsOverrides[$key] ?? $defaults[$key] ?? 0);
        });

        $subService = $this->createMock(SubscriptionService::class);
        $subService->method('isWithinLimits')->willReturn(true);

        return new RiskManager($settings, $subService);
    }

    public function test_can_trade_simulator_disabled(): void
    {
        $user = User::factory()->create();
        $rm = $this->makeRiskManager(['SIMULATOR_ENABLED' => false]);

        $result = $rm->canTrade($user->id);
        $this->assertFalse($result['allowed']);
        $this->assertEquals('Simulator is disabled', $result['reason']);
    }

    public function test_can_trade_all_checks_pass(): void
    {
        $user = User::factory()->create();
        $rm = $this->makeRiskManager();

        $result = $rm->canTrade($user->id);
        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }

    public function test_daily_loss_under_limit(): void
    {
        $user = User::factory()->create();

        // Create some losing trades today but under limit
        Trade::factory()->lost()->create([
            'user_id' => $user->id,
            'pnl' => -10.0,
        ]);

        $rm = $this->makeRiskManager(['MAX_DAILY_LOSS' => 50.0]);
        $this->assertTrue($rm->checkDailyLossLimit($user->id));
    }

    public function test_daily_loss_over_limit(): void
    {
        $user = User::factory()->create();

        Trade::factory()->lost()->create([
            'user_id' => $user->id,
            'pnl' => -60.0,
        ]);

        $rm = $this->makeRiskManager(['MAX_DAILY_LOSS' => 50.0]);
        $this->assertFalse($rm->checkDailyLossLimit($user->id));
    }

    public function test_daily_trade_count_under_limit(): void
    {
        $user = User::factory()->create();

        Trade::factory()->count(3)->create(['user_id' => $user->id]);

        $rm = $this->makeRiskManager(['MAX_DAILY_TRADES' => 48]);
        $this->assertTrue($rm->checkDailyTradeCount($user->id));
    }

    public function test_concurrent_positions_under_limit(): void
    {
        $user = User::factory()->create();

        Trade::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $rm = $this->makeRiskManager(['MAX_CONCURRENT_POSITIONS' => 3]);
        $this->assertTrue($rm->checkConcurrentPositions($user->id));
    }

    public function test_concurrent_positions_at_limit(): void
    {
        $user = User::factory()->create();

        Trade::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $rm = $this->makeRiskManager(['MAX_CONCURRENT_POSITIONS' => 3]);
        $this->assertFalse($rm->checkConcurrentPositions($user->id));
    }

    public function test_bet_size_scales_with_confidence(): void
    {
        $rm = $this->makeRiskManager(['MAX_BET_AMOUNT' => 10.0, 'MAX_BET_PERCENTAGE' => 10.0]);

        $lowConfidence = $rm->calculateBetSize(0.92, 500.0, 1);
        $highConfidence = $rm->calculateBetSize(0.98, 500.0, 1);

        $this->assertGreaterThan($lowConfidence, $highConfidence);
    }

    public function test_bet_size_never_exceeds_max(): void
    {
        $rm = $this->makeRiskManager(['MAX_BET_AMOUNT' => 10.0, 'MAX_BET_PERCENTAGE' => 10.0]);

        $bet = $rm->calculateBetSize(1.0, 10000.0, 1);
        $this->assertLessThanOrEqual(10.0, $bet);
    }

    public function test_bet_size_never_exceeds_percentage(): void
    {
        $rm = $this->makeRiskManager(['MAX_BET_AMOUNT' => 100.0, 'MAX_BET_PERCENTAGE' => 5.0]);

        $bet = $rm->calculateBetSize(1.0, 50.0, 1);
        $this->assertLessThanOrEqual(2.5, $bet); // 5% of $50
    }

    public function test_bet_size_floors_at_one(): void
    {
        $rm = $this->makeRiskManager(['MAX_BET_AMOUNT' => 10.0, 'MAX_BET_PERCENTAGE' => 50.0]);

        $bet = $rm->calculateBetSize(0.91, 100.0, 1);
        $this->assertGreaterThanOrEqual(1.0, $bet);
    }

    public function test_bet_size_with_zero_bankroll_is_still_positive(): void
    {
        $rm = $this->makeRiskManager(['MAX_BET_AMOUNT' => 10.0, 'MAX_BET_PERCENTAGE' => 10.0]);

        $bet = $rm->calculateBetSize(0.95, 0.0, 1);

        $this->assertGreaterThan(0.0, $bet);
    }
}
