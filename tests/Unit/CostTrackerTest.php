<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiDecision;
use App\Models\User;
use App\Services\AI\CostTracker;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostTrackerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCostTracker(float $budget = 100.0): CostTracker
    {
        $platformSettings = $this->createMock(PlatformSettingsService::class);
        $platformSettings->method('getFloat')->willReturn($budget);

        return new CostTracker($platformSettings);
    }

    public function test_calculate_cost_haiku(): void
    {
        $tracker = $this->makeCostTracker();
        // 1000 input tokens at $0.25/M + 500 output tokens at $1.25/M
        $cost = $tracker->calculateCost('claude-haiku-4-5-20251001', 1000, 500);

        $expected = (1000 / 1_000_000) * 0.25 + (500 / 1_000_000) * 1.25;
        $this->assertEquals(round($expected, 6), $cost);
    }

    public function test_calculate_cost_sonnet(): void
    {
        $tracker = $this->makeCostTracker();
        // 2000 input tokens at $3.00/M + 1000 output tokens at $15.00/M
        $cost = $tracker->calculateCost('claude-sonnet-4-5-20250929', 2000, 1000);

        $expected = (2000 / 1_000_000) * 3.00 + (1000 / 1_000_000) * 15.00;
        $this->assertEquals(round($expected, 6), $cost);
    }

    public function test_calculate_cost_unknown_model_uses_haiku_pricing(): void
    {
        $tracker = $this->makeCostTracker();
        $cost = $tracker->calculateCost('unknown-model', 1000, 500);

        $expected = (1000 / 1_000_000) * 0.25 + (500 / 1_000_000) * 1.25;
        $this->assertEquals(round($expected, 6), $cost);
    }

    public function test_record_usage_creates_ai_decision(): void
    {
        $user = User::factory()->create();
        $tracker = $this->makeCostTracker();

        $decision = $tracker->recordUsage($user->id, 'claude-haiku-4-5-20251001', 1000, 500, 'market_analysis');

        $this->assertInstanceOf(AiDecision::class, $decision);
        $this->assertEquals($user->id, $decision->user_id);
        $this->assertEquals('muscles', $decision->tier);
        $this->assertEquals('claude-haiku-4-5-20251001', $decision->model_used);
        $this->assertEquals(1000, $decision->tokens_input);
        $this->assertEquals(500, $decision->tokens_output);
        $this->assertEquals('market_analysis', $decision->decision_type);
    }

    public function test_record_usage_sonnet_sets_brain_tier(): void
    {
        $user = User::factory()->create();
        $tracker = $this->makeCostTracker();

        $decision = $tracker->recordUsage($user->id, 'claude-sonnet-4-5-20250929', 2000, 1000, 'loss_audit');

        $this->assertEquals('brain', $decision->tier);
    }

    public function test_is_over_budget(): void
    {
        $user = User::factory()->create();
        $tracker = $this->makeCostTracker(0.001); // Tiny budget

        // Create a decision that exceeds budget
        AiDecision::create([
            'user_id' => $user->id,
            'tier' => 'muscles',
            'model_used' => 'claude-haiku-4-5-20251001',
            'tokens_input' => 1000000,
            'tokens_output' => 1000000,
            'cost_usd' => 1.50,
            'decision_type' => 'market_analysis',
            'created_at' => now(),
        ]);

        $this->assertTrue($tracker->isOverBudget($user->id));
    }

    public function test_not_over_budget(): void
    {
        $user = User::factory()->create();
        $tracker = $this->makeCostTracker(100.0);

        $this->assertFalse($tracker->isOverBudget($user->id));
    }

    public function test_get_monthly_spend(): void
    {
        $user = User::factory()->create();
        $tracker = $this->makeCostTracker();

        AiDecision::create([
            'user_id' => $user->id,
            'tier' => 'muscles',
            'model_used' => 'claude-haiku-4-5-20251001',
            'tokens_input' => 1000,
            'tokens_output' => 500,
            'cost_usd' => 0.5,
            'decision_type' => 'market_analysis',
            'created_at' => now(),
        ]);

        AiDecision::create([
            'user_id' => $user->id,
            'tier' => 'brain',
            'model_used' => 'claude-sonnet-4-5-20250929',
            'tokens_input' => 2000,
            'tokens_output' => 1000,
            'cost_usd' => 0.3,
            'decision_type' => 'loss_audit',
            'created_at' => now(),
        ]);

        $spend = $tracker->getMonthlySpend($user->id);
        $this->assertEquals(0.8, $spend);
    }

    public function test_get_spend_by_tier(): void
    {
        $user = User::factory()->create();
        $tracker = $this->makeCostTracker();

        AiDecision::create([
            'user_id' => $user->id,
            'tier' => 'muscles',
            'model_used' => 'claude-haiku-4-5-20251001',
            'tokens_input' => 1000,
            'tokens_output' => 500,
            'cost_usd' => 0.5,
            'decision_type' => 'market_analysis',
            'created_at' => now(),
        ]);

        AiDecision::create([
            'user_id' => $user->id,
            'tier' => 'brain',
            'model_used' => 'claude-sonnet-4-5-20250929',
            'tokens_input' => 2000,
            'tokens_output' => 1000,
            'cost_usd' => 0.3,
            'decision_type' => 'loss_audit',
            'created_at' => now(),
        ]);

        $byTier = $tracker->getSpendByTier($user->id);
        $this->assertEquals(0.5, $byTier['muscles']);
        $this->assertEquals(0.3, $byTier['brain']);
    }
}
