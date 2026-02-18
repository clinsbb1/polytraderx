<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\ReflexesService;
use App\Services\Settings\SettingsService;
use App\Services\Trading\RiskManager;
use App\Services\Trading\SignalGenerator;
use Tests\TestCase;

class SignalGeneratorTest extends TestCase
{
    private function makeGenerator(
        bool $riskAllowed = true,
        string $reflexAction = 'BUY_YES',
        float $reversalProb = 0.03,
        float $betAmount = 5.0
    ): SignalGenerator
    {
        $riskManager = $this->createMock(RiskManager::class);
        $riskManager->method('canTrade')->willReturn([
            'allowed' => $riskAllowed,
            'reason' => $riskAllowed ? null : 'Daily loss limit reached',
            'checks' => [],
        ]);
        $riskManager->method('calculateBetSize')->willReturn($betAmount);

        $reflexes = $this->createMock(ReflexesService::class);
        $reflexes->method('evaluate')->willReturn([
            'action' => $reflexAction,
            'side' => $reflexAction === 'BUY_YES' ? 'YES' : ($reflexAction === 'BUY_NO' ? 'NO' : null),
            'rules_passed' => $reflexAction !== 'SKIP' ? ['all'] : [],
            'rules_failed' => $reflexAction === 'SKIP' ? ['some_rule'] : [],
            'reason' => $reflexAction === 'SKIP' ? 'Some rule failed' : 'All passed',
            'details' => ['reversal_probability' => $reversalProb],
        ]);

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getFloat')->willReturn(0.92);

        return new SignalGenerator($reflexes, $riskManager, $settings);
    }

    private function makeMarket(): array
    {
        return [
            'condition_id' => '0xtest',
            'asset' => 'BTC',
            'yes_price' => 0.96,
            'no_price' => 0.04,
            'end_time' => now()->addSeconds(30),
        ];
    }

    private function makeSpotData(): array
    {
        return ['change_since_open_pct' => 2.0, 'spot_price' => 98500];
    }

    public function test_risk_check_failing_returns_skip(): void
    {
        $generator = $this->makeGenerator(riskAllowed: false);
        $signal = $generator->generateSignal($this->makeMarket(), $this->makeSpotData(), 1, 100.0);

        $this->assertEquals('SKIP', $signal['action']);
        $this->assertStringContainsString('Risk check', $signal['reasoning']);
    }

    public function test_reflexes_skip_returns_skip(): void
    {
        $generator = $this->makeGenerator(riskAllowed: true, reflexAction: 'SKIP');
        $signal = $generator->generateSignal($this->makeMarket(), $this->makeSpotData(), 1, 100.0);

        $this->assertEquals('SKIP', $signal['action']);
        $this->assertStringContainsString('Reflexes', $signal['reasoning']);
    }

    public function test_all_passing_returns_execute(): void
    {
        $generator = $this->makeGenerator(riskAllowed: true, reflexAction: 'BUY_YES', reversalProb: 0.03);
        $signal = $generator->generateSignal($this->makeMarket(), $this->makeSpotData(), 1, 100.0);

        $this->assertEquals('EXECUTE', $signal['action']);
        $this->assertEquals('YES', $signal['side']);
        $this->assertGreaterThan(0, $signal['bet_amount']);
        $this->assertEquals('reflexes', $signal['decision_tier']);
    }

    public function test_confidence_below_threshold_returns_skip(): void
    {
        // reversal_probability of 0.15 → confidence = 0.85, below 0.92 threshold
        $generator = $this->makeGenerator(riskAllowed: true, reflexAction: 'BUY_YES', reversalProb: 0.15);
        $signal = $generator->generateSignal($this->makeMarket(), $this->makeSpotData(), 1, 100.0);

        $this->assertEquals('SKIP', $signal['action']);
        $this->assertStringContainsString('Confidence', $signal['reasoning']);
    }

    public function test_muscles_result_overrides_confidence(): void
    {
        $generator = $this->makeGenerator(riskAllowed: true, reflexAction: 'BUY_YES', reversalProb: 0.15);

        // Muscles says confidence is 0.95 — should override the 0.85 from reflexes
        $signal = $generator->generateSignal(
            $this->makeMarket(),
            $this->makeSpotData(),
            1,
            100.0,
            ['confidence' => 0.95, 'side' => 'YES'],
        );

        $this->assertEquals('EXECUTE', $signal['action']);
        $this->assertEquals('muscles', $signal['decision_tier']);
        $this->assertEquals(0.95, $signal['confidence']);
    }

    public function test_non_positive_bet_size_returns_skip(): void
    {
        $generator = $this->makeGenerator(
            riskAllowed: true,
            reflexAction: 'BUY_YES',
            reversalProb: 0.03,
            betAmount: 0.0
        );

        $signal = $generator->generateSignal($this->makeMarket(), $this->makeSpotData(), 1, 100.0);

        $this->assertEquals('SKIP', $signal['action']);
        $this->assertStringContainsString('invalid', strtolower((string) $signal['reasoning']));
    }
}
