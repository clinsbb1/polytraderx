<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\Polymarket\Eip712SignerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Eip712SignerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_order_throws_when_private_key_missing(): void
    {
        $user = User::factory()->create();
        UserCredential::create([
            'user_id' => $user->id,
            'polymarket_api_key' => 'test-key',
            'polymarket_api_secret' => base64_encode('test-secret'),
            'polymarket_api_passphrase' => 'test-pass',
            'polymarket_wallet_address' => '0x' . str_repeat('a', 40),
        ]);

        PlatformSetting::create([
            'key' => 'POLYMARKET_SIGNER_URL',
            'value' => 'http://signer.local',
            'type' => 'string',
            'group' => 'infrastructure',
            'description' => 'test',
        ]);

        $service = app(Eip712SignerService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing Polymarket private key');

        $service->signOrder($user, [
            'token_id' => '0xtoken',
            'side' => 'BUY',
            'price' => '0.9',
            'size' => '5',
            'order_type' => 'GTC',
        ]);
    }

    public function test_sign_order_calls_signer_service_and_returns_signed_payload(): void
    {
        $user = User::factory()->create();
        UserCredential::create([
            'user_id' => $user->id,
            'polymarket_api_key' => 'test-key',
            'polymarket_api_secret' => base64_encode('test-secret'),
            'polymarket_api_passphrase' => 'test-pass',
            'polymarket_wallet_address' => '0x' . str_repeat('a', 40),
            'polymarket_private_key' => '0x' . str_repeat('b', 64),
        ]);

        PlatformSetting::insert([
            [
                'key' => 'POLYMARKET_SIGNER_URL',
                'value' => 'http://signer.local',
                'type' => 'string',
                'group' => 'infrastructure',
                'description' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'POLYMARKET_SIGNER_API_KEY',
                'value' => 'signer-token',
                'type' => 'string',
                'group' => 'infrastructure',
                'description' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'POLYMARKET_SIGNER_TIMEOUT_SECONDS',
                'value' => '10',
                'type' => 'number',
                'group' => 'infrastructure',
                'description' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Http::fake([
            'http://signer.local/v1/polymarket/sign-order' => Http::response([
                'signed_order_payload' => [
                    'order' => ['tokenID' => '0xtoken'],
                    'owner' => '0x' . str_repeat('a', 40),
                    'orderType' => 'GTC',
                    'signature' => '0xsigned',
                ],
            ], 200),
        ]);

        $service = app(Eip712SignerService::class);
        $payload = $service->signOrder($user, [
            'token_id' => '0xtoken',
            'side' => 'BUY',
            'price' => '0.90',
            'size' => '5',
            'order_type' => 'GTC',
        ]);

        $this->assertSame('0xsigned', $payload['signature']);
        Http::assertSentCount(1);
    }
}

