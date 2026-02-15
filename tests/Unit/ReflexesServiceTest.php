<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\ReflexesService;
use App\Services\PriceFeed\BinanceService;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\PriceFeed\VolatilityCalculator;
use App\Services\Settings\SettingsService;
use App\Services\Trading\MarketTimingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReflexesServiceTest extends TestCase
{
    private function makeReflexes(array $settingsOverrides = [], bool $isExtreme = false, float $reversalProb = 0.02): ReflexesService
    {
        $defaults = [
            'SIMULATOR_ENABLED' => true,
            'ENTRY_WINDOW_SECONDS' => 60,
            'MONITORED_ASSETS' => 'BTC,ETH,SOL',
            'MIN_ENTRY_PRICE_THRESHOLD' => 0.92,
            'MAX_ENTRY_PRICE_THRESHOLD' => 0.08,
        ];

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturnCallback(fn($key) => (bool) ($settingsOverrides[$key] ?? $defaults[$key] ?? false));
        $settings->method('getInt')->willReturnCallback(fn($key) => (int) ($settingsOverrides[$key] ?? $defaults[$key] ?? 0));
        $settings->method('getFloat')->willReturnCallback(fn($key) => (float) ($settingsOverrides[$key] ?? $defaults[$key] ?? 0.0));
        $settings->method('get')->willReturnCallback(fn($key) => $settingsOverrides[$key] ?? $defaults[$key] ?? '');

        $priceAggregator = $this->createMock(PriceAggregator::class);
        $priceAggregator->method('detectDesync')->willReturn(null);

        $volCalc = $this->createMock(VolatilityCalculator::class);
        $volCalc->method('isVolatilityExtreme')->willReturn($isExtreme);
        $volCalc->method('estimateReversalProbability')->willReturn($reversalProb);

        $timing = new MarketTimingService($settings);

        return new ReflexesService($settings, $priceAggregator, $volCalc, $timing);
    }

    private function makeMarket(string $asset = 'BTC', float $yesPrice = 0.96, float $noPrice = 0.04, int $secondsRemaining = 30): array
    {
        return [
            'condition_id' => '0xtest',
            'question' => "Will {$asset} be higher?",
            'asset' => $asset,
            'yes_price' => $yesPrice,
            'no_price' => $noPrice,
            'seconds_remaining' => $secondsRemaining,
            'end_time' => now()->addSeconds($secondsRemaining),
        ];
    }

    private function makeSpotData(float $changePct = 2.0): array
    {
        return [
            'change_since_open_pct' => $changePct,
            'spot_price' => 98500,
            'desync_details' => null,
        ];
    }

    public function test_all_rules_pass_buy_yes(): void
    {
        $reflexes = $this->makeReflexes();
        $market = $this->makeMarket('BTC', 0.96, 0.04, 30);
        $spotData = $this->makeSpotData(2.0); // Price went UP

        $result = $reflexes->evaluate($market, $spotData, 1);

        $this->assertEquals('BUY_YES', $result['action']);
        $this->assertEquals('YES', $result['side']);
        $this->assertEmpty($result['rules_failed']);
    }

    public function test_all_rules_pass_buy_no(): void
    {
        $reflexes = $this->makeReflexes();
        $market = $this->makeMarket('BTC', 0.04, 0.96, 30);
        $spotData = $this->makeSpotData(-2.0); // Price went DOWN

        $result = $reflexes->evaluate($market, $spotData, 1);

        $this->assertEquals('BUY_NO', $result['action']);
        $this->assertEquals('NO', $result['side']);
    }

    public function test_simulator_disabled_skip(): void
    {
        $reflexes = $this->makeReflexes(['SIMULATOR_ENABLED' => false]);
        $result = $reflexes->evaluate($this->makeMarket(), $this->makeSpotData(), 1);

        $this->assertEquals('SKIP', $result['action']);
        $this->assertContains('simulator_disabled', $result['rules_failed']);
    }

    public function test_desync_detected_skip(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(true);
        $settings->method('getInt')->willReturn(60);
        $settings->method('get')->willReturn('BTC,ETH,SOL');

        $priceAggregator = $this->createMock(PriceAggregator::class);
        $priceAggregator->method('detectDesync')->willReturn('Binance says UP but Polymarket says DOWN');

        $volCalc = $this->createMock(VolatilityCalculator::class);
        $timing = new MarketTimingService($settings);

        $reflexes = new ReflexesService($settings, $priceAggregator, $volCalc, $timing);

        $spotData = $this->makeSpotData();
        $spotData['desync_details'] = 'Binance says UP but Polymarket says DOWN';

        $result = $reflexes->evaluate($this->makeMarket('BTC', 0.96, 0.04, 30), $spotData, 1);

        $this->assertEquals('SKIP', $result['action']);
        $this->assertContains('desync_detected', $result['rules_failed']);
    }

    public function test_extreme_volatility_skip(): void
    {
        $reflexes = $this->makeReflexes([], true); // isExtreme = true
        $result = $reflexes->evaluate($this->makeMarket('BTC', 0.96, 0.04, 30), $this->makeSpotData(), 1);

        $this->assertEquals('SKIP', $result['action']);
        $this->assertContains('extreme_volatility', $result['rules_failed']);
    }

    public function test_price_below_threshold_skip(): void
    {
        $reflexes = $this->makeReflexes();
        // YES at 0.70, NO at 0.30 — neither meets threshold
        $market = $this->makeMarket('BTC', 0.70, 0.30, 30);
        $result = $reflexes->evaluate($market, $this->makeSpotData(1.0), 1);

        $this->assertEquals('SKIP', $result['action']);
        $this->assertContains('price_threshold_not_met', $result['rules_failed']);
    }

    public function test_high_reversal_probability_skip(): void
    {
        $reflexes = $this->makeReflexes([], false, 0.15); // 15% reversal
        $market = $this->makeMarket('BTC', 0.96, 0.04, 30);
        $result = $reflexes->evaluate($market, $this->makeSpotData(), 1);

        $this->assertEquals('SKIP', $result['action']);
        $this->assertContains('high_reversal_risk', $result['rules_failed']);
    }

    public function test_asset_not_monitored_skip(): void
    {
        $reflexes = $this->makeReflexes(['MONITORED_ASSETS' => 'BTC,ETH']);
        $market = $this->makeMarket('SOL', 0.96, 0.04, 30);
        $result = $reflexes->evaluate($market, $this->makeSpotData(), 1);

        $this->assertEquals('SKIP', $result['action']);
        $this->assertContains('asset_not_monitored', $result['rules_failed']);
    }
}
