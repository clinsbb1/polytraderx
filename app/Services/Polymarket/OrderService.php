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
    ) {}

    public function placeOrder(string $tokenId, string $side, float $price, float $amount): array
    {
        $isDryRun = $this->settings->getBool('DRY_RUN', true, $this->userId);

        $orderPayload = $this->buildOrderPayload($tokenId, $side, $price, $amount);

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

            Log::channel('bot')->info('DRY RUN order placed', [
                'user_id' => $this->userId,
                'order' => $simulatedOrder,
            ]);

            return $simulatedOrder;
        }

        try {
            $response = $this->client->post('/order', $orderPayload);

            Log::channel('bot')->info('Order placed', [
                'user_id' => $this->userId,
                'order_id' => $response['orderID'] ?? $response['id'] ?? null,
                'token_id' => $tokenId,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('bot')->error('Failed to place order', [
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
        $isDryRun = $this->settings->getBool('DRY_RUN', true, $this->userId);

        if ($isDryRun) {
            Log::channel('bot')->info('DRY RUN order cancelled', [
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

    private function buildOrderPayload(string $tokenId, string $side, float $price, float $amount): array
    {
        return [
            'order' => [
                'tokenID' => $tokenId,
                'price' => (string) $price,
                'size' => (string) $amount,
                'side' => strtoupper($side),
                'feeRateBps' => '0',
                'nonce' => '0',
                'expiration' => '0',
            ],
            'orderType' => 'GTC',
        ];
    }
}
