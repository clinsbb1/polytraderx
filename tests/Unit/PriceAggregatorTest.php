<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PriceFeed\BinanceService;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\PriceFeed\VolatilityCalculator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceAggregatorTest extends TestCase
{
    private PriceAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/ticker/price*symbol=BTCUSDT*' => Http::response(['symbol' => 'BTCUSDT', 'price' => '98500.00'], 200),
            '*/ticker/price*symbol=ETHUSDT*' => Http::response(['symbol' => 'ETHUSDT', 'price' => '3400.00'], 200),
            '*/ticker/price*symbol=SOLUSDT*' => Http::response(['symbol' => 'SOLUSDT', 'price' => '185.00'], 200),
            '*/klines*' => Http::response($this->fakeKlines(98000, 98500), 200),
        ]);

        $binance = new BinanceService();
        $volatility = new VolatilityCalculator($binance);
        $this->aggregator = new PriceAggregator($binance, $volatility);
    }

    public function test_detect_desync_agreeing_directions(): void
    {
        // BTC went UP (98000 → 98500) and Polymarket YES > 0.5 (implies UP)
        $result = $this->aggregator->detectDesync('BTC', [
            'yes_price' => 0.96,
            'no_price' => 0.04,
        ]);

        $this->assertNull($result);
    }

    public function test_detect_desync_disagreeing_directions(): void
    {
        // BTC went UP (98000 → 98500) but Polymarket YES < 0.5 (implies DOWN)
        $result = $this->aggregator->detectDesync('BTC', [
            'yes_price' => 0.04,
            'no_price' => 0.96,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Binance says UP', $result);
        $this->assertStringContainsString('Polymarket implies DOWN', $result);
    }

    public function test_get_market_context_structure(): void
    {
        $context = $this->aggregator->getMarketContext('BTC', [
            'yes_price' => 0.96,
            'no_price' => 0.04,
        ]);

        $this->assertArrayHasKey('asset', $context);
        $this->assertArrayHasKey('spot_price', $context);
        $this->assertArrayHasKey('price_at_open', $context);
        $this->assertArrayHasKey('change_since_open_pct', $context);
        $this->assertArrayHasKey('polymarket_yes_price', $context);
        $this->assertArrayHasKey('polymarket_implied_direction', $context);
        $this->assertArrayHasKey('binance_actual_direction', $context);
        $this->assertArrayHasKey('directions_agree', $context);
        $this->assertArrayHasKey('desync_detected', $context);
        $this->assertEquals('BTC', $context['asset']);
    }

    public function test_calculate_true_probability_large_move_little_time(): void
    {
        $prob = $this->aggregator->calculateTrueProbability('BTC', 15);
        $this->assertGreaterThan(0.7, $prob);
    }

    public function test_calculate_true_probability_returns_bounded(): void
    {
        $prob = $this->aggregator->calculateTrueProbability('BTC', 60);
        $this->assertGreaterThanOrEqual(0.0, $prob);
        $this->assertLessThanOrEqual(1.0, $prob);
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
                '9850000',
                150,
                '50.25',
                '4925000',
                '0',
            ];
        }

        return $klines;
    }
}
