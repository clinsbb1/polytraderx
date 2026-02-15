<?php

declare(strict_types=1);

namespace App\Services\Polymarket;

use Illuminate\Support\Facades\Log;

class BalanceService
{
    public function __construct(private PolymarketClient $client) {}

    public function getBalance(): float
    {
        try {
            $response = $this->client->get('/balance');

            return (float) ($response['balance'] ?? $response['amount'] ?? 0);
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Failed to fetch balance', [
                'user_id' => $this->client->getUserId(),
                'message' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    public function getOpenPositions(): array
    {
        try {
            $response = $this->client->get('/positions');

            $positions = $response['data'] ?? $response;

            if (!is_array($positions)) {
                return [];
            }

            return array_map(fn(array $pos) => [
                'market_id' => $pos['market'] ?? $pos['condition_id'] ?? '',
                'token_id' => $pos['asset'] ?? $pos['token_id'] ?? '',
                'side' => $pos['side'] ?? '',
                'size' => (float) ($pos['size'] ?? $pos['amount'] ?? 0),
                'avg_price' => (float) ($pos['avgPrice'] ?? $pos['avg_price'] ?? 0),
                'current_value' => (float) ($pos['currentValue'] ?? $pos['value'] ?? 0),
            ], $positions);
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Failed to fetch positions', [
                'user_id' => $this->client->getUserId(),
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function getPositionValue(): float
    {
        $positions = $this->getOpenPositions();

        return array_sum(array_column($positions, 'current_value'));
    }

    public function getTotalEquity(): float
    {
        return $this->getBalance() + $this->getPositionValue();
    }
}
