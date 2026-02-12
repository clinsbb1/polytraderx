<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Models\User;
use App\Services\AI\AIRouter;
use App\Services\AI\AnthropicClient;
use App\Services\AI\BrainService;
use App\Services\AI\CostTracker;
use App\Services\AI\MusclesService;
use App\Services\Subscription\SubscriptionService;
use Tests\TestCase;

class AIRouterTest extends TestCase
{
    private function makeRouter(
        bool $configured = true,
        bool $hasMuscles = true,
        bool $hasBrain = true,
        ?array $musclesResult = null,
        ?AiAudit $brainResult = null,
    ): AIRouter {
        $anthropic = $this->createMock(AnthropicClient::class);
        $anthropic->method('isConfigured')->willReturn($configured);

        $muscles = $this->createMock(MusclesService::class);
        $muscles->method('analyze')->willReturn($musclesResult);

        $brain = $this->createMock(BrainService::class);
        $brain->method('auditLoss')->willReturn($brainResult);
        $brain->method('dailyReview')->willReturn($brainResult);
        $brain->method('weeklyReport')->willReturn($brainResult);

        $costTracker = $this->createMock(CostTracker::class);

        $subService = $this->createMock(SubscriptionService::class);
        $subService->method('getPlanLimits')->willReturn([
            'has_ai_muscles' => $hasMuscles,
            'has_ai_brain' => $hasBrain,
        ]);

        return new AIRouter($muscles, $brain, $costTracker, $anthropic, $subService);
    }

    public function test_muscles_returns_null_when_not_configured(): void
    {
        $router = $this->makeRouter(configured: false);

        $result = $router->getMusclesAnalysis([], [], 1);
        $this->assertNull($result);
    }

    public function test_muscles_returns_null_when_plan_lacks_muscles(): void
    {
        $router = $this->makeRouter(configured: true, hasMuscles: false);

        $result = $router->getMusclesAnalysis([], [], 1);
        $this->assertNull($result);
    }

    public function test_muscles_returns_result_when_available(): void
    {
        $expected = ['side' => 'YES', 'confidence' => 0.96];
        $router = $this->makeRouter(configured: true, hasMuscles: true, musclesResult: $expected);

        $result = $router->getMusclesAnalysis([], [], 1);
        $this->assertEquals($expected, $result);
    }

    public function test_loss_audit_returns_null_when_not_configured(): void
    {
        $router = $this->makeRouter(configured: false);
        $trade = $this->createMock(Trade::class);

        $result = $router->requestLossAudit($trade, 1);
        $this->assertNull($result);
    }

    public function test_loss_audit_returns_null_when_plan_lacks_brain(): void
    {
        $router = $this->makeRouter(configured: true, hasBrain: false);
        $trade = $this->createMock(Trade::class);

        $result = $router->requestLossAudit($trade, 1);
        $this->assertNull($result);
    }

    public function test_daily_review_returns_null_when_not_configured(): void
    {
        $router = $this->makeRouter(configured: false);
        $this->assertNull($router->requestDailyReview(1));
    }

    public function test_weekly_report_returns_null_when_not_configured(): void
    {
        $router = $this->makeRouter(configured: false);
        $this->assertNull($router->requestWeeklyReport(1));
    }

    public function test_get_available_tiers(): void
    {
        $router = $this->makeRouter(hasMuscles: true, hasBrain: false);

        $tiers = $router->getAvailableTiers(1);
        $this->assertTrue($tiers['reflexes']);
        $this->assertTrue($tiers['muscles']);
        $this->assertFalse($tiers['brain']);
    }

    public function test_get_available_tiers_all_enabled(): void
    {
        $router = $this->makeRouter(hasMuscles: true, hasBrain: true);

        $tiers = $router->getAvailableTiers(1);
        $this->assertTrue($tiers['reflexes']);
        $this->assertTrue($tiers['muscles']);
        $this->assertTrue($tiers['brain']);
    }
}
