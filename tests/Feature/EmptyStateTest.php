<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'is_active' => true,
            'is_superadmin' => false,
            'onboarding_completed' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays(7),
        ]);

        // Seed strategy params so /strategy page works
        app(SettingsService::class)->seedUserParams($this->user->id);
    }

    public function test_dashboard_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_trades_page_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/trades');
        $response->assertStatus(200);
    }

    public function test_balance_page_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/balance');
        $response->assertStatus(200);
    }

    public function test_ai_costs_page_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/ai-costs');
        $response->assertStatus(200);
    }

    public function test_audits_page_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/audits');
        $response->assertStatus(200);
    }

    public function test_logs_page_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/logs');
        $response->assertStatus(200);
    }

    public function test_strategy_page_loads_with_no_data(): void
    {
        $response = $this->actingAs($this->user)->get('/strategy');
        $response->assertStatus(200);
    }
}
