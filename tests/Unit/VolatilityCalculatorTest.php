<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PriceFeed\BinanceService;
use App\Services\PriceFeed\VolatilityCalculator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VolatilityCalculatorTest extends TestCase
{
    private VolatilityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/klines*' => Http::response($this->fakeKlines(), 200),
        ]);

        $this->calculator = new VolatilityCalculator(new BinanceService());
    }

    public function test_estimate_1min_volatility_returns_positive(): void
    {
        $vol = $this->calculator->estimate1MinVolatility('BTC');
        $this->assertGreaterThan(0, $vol);
    }

    public function test_reversal_probability_large_move_few_seconds(): void
    {
        // Large move (+2%) with only 15 seconds remaining — reversal is unlikely
        $prob = $this->calculator->estimateReversalProbability('BTC', 2.0, 15);
        $this->assertLessThan(0.15, $prob);
    }

    public function test_reversal_probability_small_move_many_seconds(): void
    {
        // Small move (+0.05%) with 120 seconds remaining — higher reversal chance
        $prob = $this->calculator->estimateReversalProbability('BTC', 0.05, 120);
        $this->assertGreaterThan(0.2, $prob);
    }

    public function test_reversal_probability_zero_seconds(): void
    {
        // No time left — reversal is impossible
        $prob = $this->calculator->estimateReversalProbability('BTC', 1.0, 0);
        $this->assertEquals(0.0, $prob);
    }

    public function test_reversal_probability_negative_change(): void
    {
        // Negative change should work the same (we use abs)
        $prob = $this->calculator->estimateReversalProbability('BTC', -2.0, 15);
        $this->assertLessThan(0.15, $prob);
    }

    public function test_normal_cdf_known_values(): void
    {
        // Use reflection to test private normalCdf
        $reflection = new \ReflectionMethod(VolatilityCalculator::class, 'normalCdf');
        $reflection->setAccessible(true);
        $calculator = new VolatilityCalculator(new BinanceService());

        // normalCdf(0) ≈ 0.5
        $this->assertEqualsWithDelta(0.5, $reflection->invoke($calculator, 0), 0.001);

        // normalCdf(-3) ≈ 0.0013
        $this->assertEqualsWithDelta(0.0013, $reflection->invoke($calculator, -3), 0.001);

        // normalCdf(3) ≈ 0.9987
        $this->assertEqualsWithDelta(0.9987, $reflection->invoke($calculator, 3), 0.001);

        // normalCdf(-1) ≈ 0.1587
        $this->assertEqualsWithDelta(0.1587, $reflection->invoke($calculator, -1), 0.001);
    }

    private function fakeKlines(): array
    {
        // 12 candles simulating 1-min BTC data with small fluctuations
        $base = 98000;
        $klines = [];
        $prices = [98000, 98050, 97980, 98100, 98020, 98150, 98080, 98200, 98120, 98250, 98180, 98300];

        foreach ($prices as $i => $close) {
            $open = $i > 0 ? $prices[$i - 1] : $base;
            $klines[] = [
                1707700000000 + ($i * 60000), // open_time
                (string) $open,                // open
                (string) ($close + 30),        // high
                (string) ($close - 30),        // low
                (string) $close,               // close
                '100.5',                       // volume
                1707700059999 + ($i * 60000), // close_time
                '9850000',                     // quote_volume
                150,                           // trades
                '50.25',                       // taker_buy_base
                '4925000',                     // taker_buy_quote
                '0',                           // ignore
            ];
        }

        return $klines;
    }
}
