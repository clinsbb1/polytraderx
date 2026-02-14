<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DryRunFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createSubscribedUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
            'is_superadmin' => false,
            'onboarding_completed' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays(7),
        ], $overrides));
    }

    public function test_dry_run_setting_defaults_to_true(): void
    {
        $user = $this->createSubscribedUser();

        $settings = app(SettingsService::class);
        $settings->seedUserParams($user->id);

        $this->assertTrue($settings->getBool('DRY_RUN', true, $user->id));
    }

    public function test_dry_run_user_has_no_polymarket_keys(): void
    {
        $user = $this->createSubscribedUser();

        $this->assertNull($user->credential);
        $this->assertFalse($user->hasPolymarketConfigured());
    }

    public function test_simulated_balance_calculation(): void
    {
        $user = $this->createSubscribedUser();

        Trade::factory()->for($user)->create([
            'status' => 'won',
            'amount' => '5.00',
            'pnl' => '5.50',
            'resolved_at' => now(),
        ]);

        Trade::factory()->for($user)->create([
            'status' => 'lost',
            'amount' => '4.00',
            'pnl' => '-3.00',
            'resolved_at' => now(),
        ]);

        $totalPnl = (float) Trade::forUser($user->id)->sum('pnl');
        $this->assertEquals(2.50, $totalPnl);
    }

    public function test_dashboard_loads_for_dry_run_user(): void
    {
        $user = $this->createSubscribedUser();

        $settings = app(SettingsService::class);
        $settings->seedUserParams($user->id);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('DRY RUN', false);
    }

    public function test_dashboard_shows_live_mode_when_dry_run_disabled(): void
    {
        $user = $this->createSubscribedUser();

        $settings = app(SettingsService::class);
        $settings->seedUserParams($user->id);
        $settings->set('DRY_RUN', 'false', 'test', $user->id);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('LIVE', false);
    }

    public function test_csv_export_returns_csv_content_type(): void
    {
        $user = $this->createSubscribedUser();

        $response = $this->actingAs($user)->get('/trades/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_csv_export_contains_trade_data(): void
    {
        $user = $this->createSubscribedUser();

        Trade::factory()->for($user)->create([
            'asset' => 'BTC',
            'status' => 'won',
            'pnl' => '2.50',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/trades/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('BTC', $response->streamedContent());
    }

    public function test_dry_run_trade_pnl_sum_correct(): void
    {
        $user = $this->createSubscribedUser();

        Trade::factory()->for($user)->count(3)->create([
            'status' => 'won',
            'pnl' => '2.00',
            'resolved_at' => now(),
        ]);

        Trade::factory()->for($user)->count(2)->create([
            'status' => 'lost',
            'pnl' => '-1.50',
            'resolved_at' => now(),
        ]);

        $totalPnl = (float) Trade::forUser($user->id)->sum('pnl');
        $this->assertEquals(3.00, $totalPnl);
    }
}
