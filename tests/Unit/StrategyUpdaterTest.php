<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAudit;
use App\Models\User;
use App\Services\Audit\StrategyUpdater;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrategyUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private function makeUpdater(): StrategyUpdater
    {
        $settings = $this->createMock(SettingsService::class);

        return new StrategyUpdater($settings);
    }

    private function makeAudit(int $userId, array $fixes = []): AiAudit
    {
        return AiAudit::create([
            'user_id' => $userId,
            'trigger' => 'post_loss',
            'losing_trade_ids' => [1],
            'analysis' => 'Test analysis',
            'suggested_fixes' => $fixes,
            'status' => 'pending_review',
            'created_at' => now(),
        ]);
    }

    public function test_validate_valid_float_param(): void
    {
        $updater = $this->makeUpdater();

        $this->assertTrue($updater->validateFixValue('MIN_CONFIDENCE_SCORE', 0.95));
        $this->assertTrue($updater->validateFixValue('MAX_BET_AMOUNT', 50.0));
        $this->assertTrue($updater->validateFixValue('MAX_DAILY_LOSS', 100.0));
    }

    public function test_validate_rejects_out_of_bounds(): void
    {
        $updater = $this->makeUpdater();

        $this->assertFalse($updater->validateFixValue('MIN_CONFIDENCE_SCORE', 0.1)); // Below 0.5
        $this->assertFalse($updater->validateFixValue('MIN_CONFIDENCE_SCORE', 1.5)); // Above 1.0
        $this->assertFalse($updater->validateFixValue('MAX_BET_AMOUNT', 0.001)); // Below 0.01
        $this->assertFalse($updater->validateFixValue('MAX_BET_AMOUNT', 5000)); // Above 1000
    }

    public function test_validate_rejects_unknown_param(): void
    {
        $updater = $this->makeUpdater();

        $this->assertFalse($updater->validateFixValue('UNKNOWN_PARAM', 1.0));
        $this->assertFalse($updater->validateFixValue('API_KEY', 'secret'));
    }

    public function test_validate_int_param(): void
    {
        $updater = $this->makeUpdater();

        $this->assertTrue($updater->validateFixValue('MAX_DAILY_TRADES', 50));
        $this->assertFalse($updater->validateFixValue('MAX_DAILY_TRADES', 0)); // Below 1
        $this->assertFalse($updater->validateFixValue('MAX_DAILY_TRADES', 1000)); // Above 500
    }

    public function test_validate_bool_param(): void
    {
        $updater = $this->makeUpdater();

        $this->assertTrue($updater->validateFixValue('SIMULATOR_ENABLED', true));
        $this->assertTrue($updater->validateFixValue('SIMULATOR_ENABLED', false));
        $this->assertTrue($updater->validateFixValue('DRY_RUN', '1'));
        $this->assertTrue($updater->validateFixValue('DRY_RUN', '0'));
    }

    public function test_apply_fix_updates_audit(): void
    {
        $user = User::factory()->create();
        $updater = $this->makeUpdater();

        $audit = $this->makeAudit($user->id, [
            ['param_key' => 'MIN_CONFIDENCE_SCORE', 'current_value' => '0.92', 'suggested_value' => 0.95, 'reason' => 'Too many losses', 'action' => 'auto_apply'],
        ]);

        $result = $updater->applyFix($audit, 0, $user->id);
        $this->assertTrue($result);

        $audit->refresh();
        $this->assertTrue($audit->suggested_fixes[0]['applied']);
        $this->assertNotNull($audit->suggested_fixes[0]['applied_at']);
    }

    public function test_apply_fix_rejects_invalid_value(): void
    {
        $user = User::factory()->create();
        $updater = $this->makeUpdater();

        $audit = $this->makeAudit($user->id, [
            ['param_key' => 'MIN_CONFIDENCE_SCORE', 'current_value' => '0.92', 'suggested_value' => 2.0, 'reason' => 'Bad value', 'action' => 'auto_apply'],
        ]);

        $result = $updater->applyFix($audit, 0, $user->id);
        $this->assertFalse($result);
    }

    public function test_reject_fix(): void
    {
        $user = User::factory()->create();
        $updater = $this->makeUpdater();

        $audit = $this->makeAudit($user->id, [
            ['param_key' => 'MIN_CONFIDENCE_SCORE', 'current_value' => '0.92', 'suggested_value' => 0.95, 'reason' => 'Increase', 'action' => 'review_required'],
        ]);

        $result = $updater->rejectFix($audit, 0, 'Not appropriate');
        $this->assertTrue($result);

        $audit->refresh();
        $this->assertTrue($audit->suggested_fixes[0]['rejected']);
        $this->assertEquals('Not appropriate', $audit->suggested_fixes[0]['reject_reason']);
    }

    public function test_auto_apply_fixes(): void
    {
        $user = User::factory()->create();
        $updater = $this->makeUpdater();

        $audit = $this->makeAudit($user->id, [
            ['param_key' => 'MIN_CONFIDENCE_SCORE', 'suggested_value' => 0.95, 'action' => 'auto_apply'],
            ['param_key' => 'MAX_BET_AMOUNT', 'suggested_value' => 25.0, 'action' => 'review_required'],
            ['param_key' => 'ENTRY_WINDOW_SECONDS', 'suggested_value' => 45, 'action' => 'auto_apply'],
        ]);

        $applied = $updater->autoApplyFixes($audit, $user->id);
        $this->assertEquals(2, $applied); // Only auto_apply ones
    }

    public function test_apply_fix_invalid_index_returns_false(): void
    {
        $user = User::factory()->create();
        $updater = $this->makeUpdater();

        $audit = $this->makeAudit($user->id, []);
        $this->assertFalse($updater->applyFix($audit, 0, $user->id));
        $this->assertFalse($updater->applyFix($audit, 99, $user->id));
    }
}
