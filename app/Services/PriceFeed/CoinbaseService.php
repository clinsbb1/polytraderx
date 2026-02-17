<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CoinbaseService
{
    private string $baseUrl;

    private const ASSET_PRODUCTS = [
        'BTC' => 'BTC-USD',
        'ETH' => 'ETH-USD',
        'SOL' => 'SOL-USD',
        'XRP' => 'XRP-USD',
    ];

    private const CACHE_TTL_SECONDS = 3;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.coinbase.base_url', 'https://api.exchange.coinbase.com'), '/');
    }

    public function getPriceForAsset(string $asset): float
    {
        $product = $this->resolveProduct($asset);
        $cacheKey = "coinbase:price:{$product}";

        return (float) Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($product) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/products/{$product}/ticker");

            if (!$response->successful()) {
                throw new \RuntimeException("Coinbase ticker error ({$response->status()}): {$response->body()}");
            }

            return (float) $response->json('price');
        });
    }

    public function getPriceChange(string $asset, int $minutes = 15): array
    {
        $product = $this->resolveProduct($asset);
        $limit = max(2, min(300, $minutes + 1));
        $cacheKey = "coinbase:candles:{$product}:{$limit}";

        $candles = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($product, $limit) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/products/{$product}/candles", [
                'granularity' => 60,
                'limit' => $limit,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Coinbase candles error ({$response->status()}): {$response->body()}");
            }

            $raw = $response->json();
            if (!is_array($raw)) {
                return [];
            }

            // Coinbase returns newest-first.
            $rows = array_reverse($raw);
            return array_values(array_filter($rows, fn($row) => is_array($row) && count($row) >= 5));
        });

        if (empty($candles)) {
            $current = $this->getPriceForAsset($asset);
            return [
                'open' => $current,
                'current' => $current,
                'change_pct' => 0.0,
                'high' => $current,
                'low' => $current,
            ];
        }

        // [time, low, high, open, close, volume]
        $first = $candles[0];
        $last = $candles[count($candles) - 1];
        $openPrice = (float) ($first[3] ?? 0);
        $currentPrice = (float) ($last[4] ?? 0);
        $high = max(array_map(fn($c) => (float) ($c[2] ?? 0), $candles));
        $low = min(array_map(fn($c) => (float) ($c[1] ?? 0), $candles));
        $changePct = $openPrice > 0 ? (($currentPrice - $openPrice) / $openPrice) * 100 : 0.0;

        return [
            'open' => $openPrice,
            'current' => $currentPrice,
            'change_pct' => round($changePct, 4),
            'high' => $high,
            'low' => $low,
        ];
    }

    private function resolveProduct(string $asset): string
    {
        $product = self::ASSET_PRODUCTS[strtoupper($asset)] ?? null;
        if ($product === null) {
            throw new \InvalidArgumentException("Unsupported asset for Coinbase: {$asset}");
        }

        return $product;
    }
}

