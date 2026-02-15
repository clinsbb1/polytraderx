<?php

declare(strict_types=1);

namespace App\Services\Polymarket;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private PolymarketClient $client,
        private SettingsService $settings,
        private int $userId,
        private Eip712SignerService $signer,
    ) {}

    public function placeOrder(string $tokenId, string $side, float $price, float $amount): array
    {
        // Platform-level enforcement: force simulation if live trading disabled
        $liveEnabled = app(\App\Services\Settings\PlatformSettingsService::class)
            ->getBool('FEATURE_LIVE_TRADING', false);

        if (!$liveEnabled) {
            $isDryRun = true;  // Force simulation regardless of user preference
        } else {
            $isDryRun = $this->settings->getBool('DRY_RUN', true, $this->userId);
        }

        $orderIntent = $this->buildOrderIntent($tokenId, $side, $price, $amount);

        if ($isDryRun) {
            $simulatedOrder = [
                'order_id' => 'DRY_RUN_' . uniqid(),
                'status' => 'simulated',
                'token_id' => $tokenId,
                'side' => strtoupper($side),
                'price' => (string) $price,
                'size' => (string) $amount,
                'dry_run' => true,
                'created_at' => now()->toIso8601String(),
            ];

            Log::channel('simulator')->info('DRY RUN order placed', [
                'user_id' => $this->userId,
                'order' => $simulatedOrder,
            ]);

            return $simulatedOrder;
        }

        try {
            $signedOrderPayload = $this->signer->signOrder($this->client->getUser(), $orderIntent);
            $response = $this->client->post('/order', $signedOrderPayload);

            Log::channel('simulator')->info('Order placed', [
                'user_id' => $this->userId,
                'order_id' => $response['orderID'] ?? $response['id'] ?? null,
                'token_id' => $tokenId,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Failed to place order', [
                'user_id' => $this->userId,
                'token_id' => $tokenId,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function cancelOrder(string $orderId): array
    {
        // Platform-level enforcement: force simulation if live trading disabled
        $liveEnabled = app(\App\Services\Settings\PlatformSettingsService::class)
            ->getBool('FEATURE_LIVE_TRADING', false);

        if (!$liveEnabled) {
            $isDryRun = true;  // Force simulation regardless of user preference
        } else {
            $isDryRun = $this->settings->getBool('DRY_RUN', true, $this->userId);
        }

        if ($isDryRun) {
            Log::channel('simulator')->info('Simulated order cancelled', [
                'user_id' => $this->userId,
                'order_id' => $orderId,
            ]);

            return ['status' => 'simulated_cancel', 'order_id' => $orderId, 'dry_run' => true];
        }

        return $this->client->delete("/order/{$orderId}");
    }

    public function getOpenOrders(): array
    {
        return $this->client->get('/orders', ['open' => 'true']);
    }

    public function getOrderStatus(string $orderId): array
    {
        return $this->client->get("/order/{$orderId}");
    }

    public function getUserTrades(int $limit = 50): array
    {
        return $this->client->get('/trades', ['limit' => (string) $limit]);
    }

    private function buildOrderIntent(string $tokenId, string $side, float $price, float $amount): array
    {
        return [
            'token_id' => $tokenId,
            'side' => strtoupper($side),
            'price' => (string) $price,
            'size' => (string) $amount,
            'order_type' => 'GTC',
        ];
    }
}
