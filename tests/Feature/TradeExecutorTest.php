<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Trade;
use App\Models\TradeLog;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\Settings\SettingsService;
use App\Services\Trading\TradeExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TradeExecutorTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        UserCredential::create([
            'user_id' => $user->id,
            'polymarket_api_key' => 'test-key',
            'polymarket_api_secret' => base64_encode('test-secret'),
            'polymarket_api_passphrase' => 'test-pass',
            'polymarket_wallet_address' => '0x' . str_repeat('a', 40),
        ]);
        return $user;
    }

    private function makeSignal(): array
    {
        return [
            'action' => 'EXECUTE',
            'side' => 'YES',
            'confidence' => 0.95,
            'bet_amount' => 5.0,
            'decision_tier' => 'reflexes',
            'reasoning' => 'All rules passed',
            'risk_check' => ['allowed' => true, 'checks' => []],
        ];
    }

    private function makeMarket(): array
    {
        return [
            'condition_id' => '0xmarket123',
            'slug' => 'btc-15min-up',
            'question' => 'Will BTC be higher?',
            'asset' => 'BTC',
            'yes_price' => 0.96,
            'no_price' => 0.04,
            'yes_token_id' => '0xyes_token',
            'no_token_id' => '0xno_token',
            'end_time' => now()->addMinutes(15),
            'seconds_remaining' => 30,
        ];
    }

    private function makeSpotData(): array
    {
        return [
            'spot_price' => 98500.0,
            'price_at_open' => 96000.0,
            'change_since_open_pct' => 2.6,
            'change_1m_pct' => 0.1,
            'change_5m_pct' => 0.8,
        ];
    }

    public function test_execute_creates_trade_and_log_dry_run(): void
    {
        Http::fake([
            '*/time' => Http::response(['time' => time()], 200),
            '*' => Http::response([], 200),
        ]);

        $user = $this->makeUser();

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(true); // DRY_RUN = true

        $executor = new TradeExecutor($settings);
        $trade = $executor->execute($this->makeSignal(), $this->makeMarket(), $this->makeSpotData(), $user);

        $this->assertNotNull($trade);
        $this->assertInstanceOf(Trade::class, $trade);
        $this->assertEquals('open', $trade->status);
        $this->assertEquals('BTC', $trade->asset);
        $this->assertEquals('YES', $trade->side);
        $this->assertEquals(5.0, (float) $trade->amount);
        $this->assertEquals($user->id, $trade->user_id);

        // Check dry run order ID in reasoning
        $reasoning = $trade->decision_reasoning;
        $this->assertArrayHasKey('order_result', $reasoning);
        $this->assertTrue($reasoning['order_result']['dry_run']);
        $this->assertStringStartsWith('DRY_RUN_', $reasoning['order_result']['order_id']);

        // Check trade log was created
        $log = TradeLog::where('trade_id', $trade->id)->where('event', 'placed')->first();
        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertArrayHasKey('market', $log->data);
        $this->assertArrayHasKey('decision', $log->data);
        $this->assertArrayHasKey('order', $log->data);
    }

    public function test_execute_failure_creates_cancelled_trade(): void
    {
        // Fake a server error from Polymarket
        Http::fake([
            '*' => Http::response(['error' => 'server error'], 500),
        ]);

        $user = $this->makeUser();

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(false); // DRY_RUN = false (real call, will fail)

        $executor = new TradeExecutor($settings);
        $trade = $executor->execute($this->makeSignal(), $this->makeMarket(), $this->makeSpotData(), $user);

        $this->assertNull($trade);

        // Check that a cancelled trade was created
        $cancelled = Trade::where('user_id', $user->id)->where('status', 'cancelled')->first();
        $this->assertNotNull($cancelled);

        // Check error log
        $errorLog = TradeLog::where('trade_id', $cancelled->id)->where('event', 'error')->first();
        $this->assertNotNull($errorLog);
        $this->assertArrayHasKey('error', $errorLog->data);
    }

    public function test_execute_skips_when_bet_amount_is_zero(): void
    {
        $user = $this->makeUser();
        $signal = $this->makeSignal();
        $signal['bet_amount'] = 0.0;

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(true);

        $executor = new TradeExecutor($settings);
        $trade = $executor->execute($signal, $this->makeMarket(), $this->makeSpotData(), $user);

        $this->assertNull($trade);
        $this->assertDatabaseCount('trades', 0);
    }

    public function test_execute_skips_when_entry_price_is_zero(): void
    {
        $user = $this->makeUser();
        $market = $this->makeMarket();
        $market['yes_price'] = 0.0;

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(true);

        $executor = new TradeExecutor($settings);
        $trade = $executor->execute($this->makeSignal(), $market, $this->makeSpotData(), $user);

        $this->assertNull($trade);
        $this->assertDatabaseCount('trades', 0);
    }

    public function test_execute_prefers_seconds_remaining_for_market_end_time(): void
    {
        Http::fake([
            '*/time' => Http::response(['time' => time()], 200),
            '*' => Http::response([], 200),
        ]);

        $user = $this->makeUser();
        $market = $this->makeMarket();
        $market['seconds_remaining'] = 25;
        $market['end_time'] = now()->addHours(3); // Deliberately wrong/far value.

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(true);

        $executor = new TradeExecutor($settings);
        $trade = $executor->execute($this->makeSignal(), $market, $this->makeSpotData(), $user);

        $this->assertNotNull($trade);
        $trade->refresh();
        $this->assertNotNull($trade->market_end_time);
        $this->assertTrue(
            $trade->market_end_time->between(now()->addSeconds(10), now()->addSeconds(45)),
            'Expected market_end_time to be based on seconds_remaining, not far-future end_time.'
        );
    }
}
