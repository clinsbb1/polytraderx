<?php

declare(strict_types=1);

namespace Tests\Feature;

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
        Cache::forget('health_check');
    }

    public function test_health_returns_ok_status(): void
    {
        Http::fake([
            'api.binance.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => ['database', 'binance'],
                'stats' => ['active_users', 'trades_today'],
            ]);
    }

    public function test_health_reports_database_ok(): void
    {
        Http::fake([
            'api.binance.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonPath('services.database', 'ok');
    }

    public function test_health_reports_binance_degraded_on_failure(): void
    {
        Http::fake([
            'api.binance.com/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonPath('services.binance', 'degraded');
    }
}
