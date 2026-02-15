<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached health check between tests
        Cache::forget('health_check:public');
        Cache::forget('health_check:superadmin');
    }

    public function test_health_returns_ok_status(): void
    {
        Http::fake([
            'api.binance.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'timestamp'])
            ->assertJsonMissingPath('services')
            ->assertJsonMissingPath('stats');
    }

    public function test_health_reports_database_ok_for_superadmin(): void
    {
        Http::fake([
            'api.binance.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'subscription_plan' => 'pro',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($superadmin)->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonPath('services.database', 'ok');
    }

    public function test_health_reports_binance_degraded_on_failure_for_superadmin(): void
    {
        Http::fake([
            'api.binance.com/*' => Http::response([], 500),
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'subscription_plan' => 'pro',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($superadmin)->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonPath('services.binance', 'degraded');
    }
}
