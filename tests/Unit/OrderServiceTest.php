<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\Polymarket\OrderService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_returns_simulated_order(): void
    {
        Http::fake();

        $user = User::factory()->create();
        UserCredential::create([
            'user_id' => $user->id,
            'polymarket_api_key' => 'test-key-uuid',
            'polymarket_api_secret' => base64_encode('test-secret'),
            'polymarket_api_passphrase' => 'test-passphrase-hex',
            'polymarket_wallet_address' => '0x' . str_repeat('a', 40),
        ]);

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')
            ->with('DRY_RUN', true, $user->id)
            ->willReturn(true);

        $client = new PolymarketClient($user);
        $orderService = new OrderService($client, $settings, $user->id);

        $result = $orderService->placeOrder('0xtoken123', 'BUY', 0.96, 5.0);

        $this->assertTrue($result['dry_run']);
        $this->assertEquals('simulated', $result['status']);
        $this->assertStringStartsWith('DRY_RUN_', $result['order_id']);
        $this->assertEquals('0xtoken123', $result['token_id']);
        $this->assertEquals('BUY', $result['side']);

        // Verify no HTTP calls were made to Polymarket
        Http::assertNothingSent();
    }

    public function test_dry_run_cancel_returns_simulated(): void
    {
        Http::fake();

        $user = User::factory()->create();
        UserCredential::create([
            'user_id' => $user->id,
            'polymarket_api_key' => 'test-key-uuid',
            'polymarket_api_secret' => base64_encode('test-secret'),
            'polymarket_api_passphrase' => 'test-passphrase-hex',
            'polymarket_wallet_address' => '0x' . str_repeat('a', 40),
        ]);

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')
            ->with('DRY_RUN', true, $user->id)
            ->willReturn(true);

        $client = new PolymarketClient($user);
        $orderService = new OrderService($client, $settings, $user->id);

        $result = $orderService->cancelOrder('order-123');

        $this->assertTrue($result['dry_run']);
        $this->assertEquals('simulated_cancel', $result['status']);
        Http::assertNothingSent();
    }
}
