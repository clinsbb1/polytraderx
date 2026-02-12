<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Trade;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\Polymarket\BalanceService;
use App\Services\Polymarket\MarketService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\PriceFeed\BinanceService;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\Settings\SettingsService;
use App\Services\Trading\MarketTimingService;
use App\Services\Trading\SignalGenerator;
use App\Services\Trading\StrategyEngine;
use App\Services\Trading\TradeExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StrategyEngineTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays(7),
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

    public function test_idempotency_no_duplicate_trades(): void
    {
        Http::fake([
            '*/markets*' => Http::response([
                'data' => [[
                    'condition_id' => '0xmarket_abc',
                    'question' => 'Will BTC be higher at 3:00 PM?',
                    'slug' => 'btc-15min-up',
                    'tokens' => [
                        ['outcome' => 'Yes', 'price' => '0.96', 'token_id' => '0xyes'],
                        ['outcome' => 'No', 'price' => '0.04', 'token_id' => '0xno'],
                    ],
                    'end_date_iso' => now()->addSeconds(30)->toIso8601String(),
                    'volume' => '10000',
                ]],
            ], 200),
            '*/ticker/price*' => Http::response(['symbol' => 'BTCUSDT', 'price' => '98500.00'], 200),
            '*/klines*' => Http::response($this->fakeKlines(96000, 98500), 200),
            '*/balance*' => Http::response(['balance' => '100.00'], 200),
            '*/positions*' => Http::response([], 200),
            '*' => Http::response([], 200),
        ]);

        $user = $this->makeUser();

        // Pre-create an existing open trade for this market
        Trade::factory()->create([
            'user_id' => $user->id,
            'market_id' => '0xmarket_abc',
            'status' => 'open',
        ]);

        $engine = app(StrategyEngine::class);
        $result = $engine->runForUser($user);

        // Should have been skipped due to existing position
        $this->assertEquals(0, $result['trades_placed']);
        $this->assertNotEmpty($result['skipped']);

        // Verify only 1 trade for this market (the pre-existing one)
        $tradeCount = Trade::where('user_id', $user->id)
            ->where('market_id', '0xmarket_abc')
            ->count();
        $this->assertEquals(1, $tradeCount);
    }

    private function fakeKlines(float $startPrice, float $endPrice): array
    {
        $klines = [];
        $steps = 15;
        $increment = ($endPrice - $startPrice) / $steps;

        for ($i = 0; $i <= $steps; $i++) {
            $price = $startPrice + ($increment * $i);
            $klines[] = [
                1707700000000 + ($i * 60000),
                (string) ($price - $increment),
                (string) ($price + 20),
                (string) ($price - 20),
                (string) $price,
                '100.5',
                1707700059999 + ($i * 60000),
                '9850000', 150, '50.25', '4925000', '0',
            ];
        }

        return $klines;
    }
}
