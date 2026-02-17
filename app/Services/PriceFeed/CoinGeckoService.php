<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CoinGeckoService
{
    private string $baseUrl;

    private const ASSET_IDS = [
        'BTC' => 'bitcoin',
        'ETH' => 'ethereum',
        'SOL' => 'solana',
        'XRP' => 'ripple',
    ];

    private const CACHE_TTL_SECONDS = 5;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.coingecko.base_url', 'https://api.coingecko.com/api/v3'), '/');
    }

    public function getPriceForAsset(string $asset): float
    {
        $coinId = $this->resolveCoinId($asset);
        $cacheKey = "coingecko:price:{$coinId}";

        return (float) Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($coinId) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/simple/price", [
                'ids' => $coinId,
                'vs_currencies' => 'usd',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("CoinGecko simple price error ({$response->status()}): {$response->body()}");
            }

            $usd = $response->json("{$coinId}.usd");
            if ($usd === null) {
                throw new \RuntimeException("CoinGecko price payload missing USD for {$coinId}");
            }

            return (float) $usd;
        });
    }

    public function getPriceChange(string $asset, int $minutes = 15): array
    {
        $coinId = $this->resolveCoinId($asset);
        $cacheKey = "coingecko:chart:{$coinId}";

        $series = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($coinId) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/coins/{$coinId}/market_chart", [
                'vs_currency' => 'usd',
                'days' => 1,
                'interval' => 'minutely',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("CoinGecko market chart error ({$response->status()}): {$response->body()}");
            }

            $prices = $response->json('prices');
            if (!is_array($prices)) {
                return [];
            }

            return array_values(array_filter($prices, fn($row) => is_array($row) && count($row) >= 2));
        });

        if (empty($series)) {
            $current = $this->getPriceForAsset($asset);
            return [
                'open' => $current,
                'current' => $current,
                'change_pct' => 0.0,
                'high' => $current,
                'low' => $current,
            ];
        }

        $nowMs = now()->valueOf();
        $targetMs = $nowMs - (max(1, $minutes) * 60 * 1000);

        $open = (float) $series[0][1];
        $window = [];

        foreach ($series as $point) {
            $ts = (int) $point[0];
            $price = (float) $point[1];

            if ($ts <= $targetMs) {
                $open = $price;
            }

            if ($ts >= $targetMs) {
                $window[] = $price;
            }
        }

        if (empty($window)) {
            $window = [(float) end($series)[1]];
        }

        $current = (float) end($window);
        $high = max($window);
        $low = min($window);
        $changePct = $open > 0 ? (($current - $open) / $open) * 100 : 0.0;

        return [
            'open' => $open,
            'current' => $current,
            'change_pct' => round($changePct, 4),
            'high' => $high,
            'low' => $low,
        ];
    }

    private function resolveCoinId(string $asset): string
    {
        $coinId = self::ASSET_IDS[strtoupper($asset)] ?? null;
        if ($coinId === null) {
            throw new \InvalidArgumentException("Unsupported asset for CoinGecko: {$asset}");
        }

        return $coinId;
    }
}

