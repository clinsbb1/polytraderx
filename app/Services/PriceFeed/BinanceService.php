<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use App\Exceptions\BinanceApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceService
{
    private string $baseUrl;

    private const ASSET_SYMBOLS = [
        'BTC' => 'BTCUSDT',
        'ETH' => 'ETHUSDT',
        'SOL' => 'SOLUSDT',
        'XRP' => 'XRPUSDT',
    ];

    private const RATE_LIMIT_KEY = 'binance:request_count';
    private const RATE_LIMIT_MAX = 1100; // Stay under 1200/min limit
    private const PRICE_CACHE_TTL = 3;

    public function __construct()
    {
        $this->baseUrl = config('services.binance.base_url', 'https://api.binance.com/api/v3');
    }

    public function getCurrentPrice(string $symbol): float
    {
        $cacheKey = "binance:price:{$symbol}";
        $staleCacheKey = "binance:price_stale:{$symbol}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (float) $cached;
        }

        try {
            $this->throttle();

            $response = Http::timeout(10)->get("{$this->baseUrl}/ticker/price", [
                'symbol' => $symbol,
            ]);

            if (!$response->successful()) {
                throw new BinanceApiException(
                    "Binance API error ({$response->status()}): {$response->body()}",
                    $response->status(),
                    $response->body(),
                );
            }

            $price = (float) $response->json('price');
            Cache::put($cacheKey, $price, self::PRICE_CACHE_TTL);
            Cache::put($staleCacheKey, $price, 300); // 5 min stale cache

            return $price;
        } catch (\Throwable $e) {
            // Return stale cached price if available
            $stalePrice = Cache::get($staleCacheKey);
            if ($stalePrice !== null) {
                Log::channel('simulator')->warning("Binance API failed, using stale price for {$symbol}", [
                    'stale_price' => $stalePrice,
                    'error' => $e->getMessage(),
                ]);
                return (float) $stalePrice;
            }

            throw $e;
        }
    }

    public function getPriceForAsset(string $asset): float
    {
        $symbol = self::ASSET_SYMBOLS[strtoupper($asset)] ?? null;

        if ($symbol === null) {
            throw new \InvalidArgumentException("Unsupported asset: {$asset}");
        }

        return $this->getCurrentPrice($symbol);
    }

    public function getKlines(string $symbol, string $interval = '1m', int $limit = 15): array
    {
        $cacheKey = "binance:klines:{$symbol}:{$interval}:{$limit}";

        return Cache::remember($cacheKey, self::PRICE_CACHE_TTL, function () use ($symbol, $interval, $limit) {
            $this->throttle();

            $response = Http::timeout(10)->get("{$this->baseUrl}/klines", [
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Binance klines error ({$response->status()}): {$response->body()}");
            }

            $raw = $response->json();

            return array_map(fn(array $candle) => [
                'open_time' => (int) $candle[0],
                'open' => (float) $candle[1],
                'high' => (float) $candle[2],
                'low' => (float) $candle[3],
                'close' => (float) $candle[4],
                'volume' => (float) $candle[5],
                'close_time' => (int) $candle[6],
            ], $raw);
        });
    }

    public function getPriceChange(string $asset, int $minutes = 15): array
    {
        $symbol = self::ASSET_SYMBOLS[strtoupper($asset)] ?? null;

        if ($symbol === null) {
            throw new \InvalidArgumentException("Unsupported asset: {$asset}");
        }

        $klines = $this->getKlines($symbol, '1m', $minutes);

        if (empty($klines)) {
            return [
                'open' => 0.0,
                'current' => 0.0,
                'change_pct' => 0.0,
                'high' => 0.0,
                'low' => 0.0,
            ];
        }

        $openPrice = $klines[0]['open'];
        $currentPrice = end($klines)['close'];
        $high = max(array_column($klines, 'high'));
        $low = min(array_column($klines, 'low'));
        $changePct = $openPrice > 0 ? (($currentPrice - $openPrice) / $openPrice) * 100 : 0.0;

        return [
            'open' => $openPrice,
            'current' => $currentPrice,
            'change_pct' => round($changePct, 4),
            'high' => $high,
            'low' => $low,
        ];
    }

    public function get1MinChanges(string $asset, int $count = 5): array
    {
        $symbol = self::ASSET_SYMBOLS[strtoupper($asset)] ?? null;

        if ($symbol === null) {
            throw new \InvalidArgumentException("Unsupported asset: {$asset}");
        }

        $klines = $this->getKlines($symbol, '1m', $count + 1);

        $changes = [];
        for ($i = 1; $i < count($klines); $i++) {
            $prevClose = $klines[$i - 1]['close'];
            $currClose = $klines[$i]['close'];
            $changes[] = $prevClose > 0 ? (($currClose - $prevClose) / $prevClose) * 100 : 0.0;
        }

        return $changes;
    }

    public function getMultiAssetPrices(array $assets = ['BTC', 'ETH', 'SOL']): array
    {
        $prices = [];

        foreach ($assets as $asset) {
            try {
                $prices[$asset] = $this->getPriceForAsset($asset);
            } catch (\Exception $e) {
                Log::channel('simulator')->warning("Failed to fetch {$asset} price from Binance", [
                    'message' => $e->getMessage(),
                ]);
                $prices[$asset] = 0.0;
            }
        }

        return $prices;
    }

    private function throttle(): void
    {
        $count = (int) Cache::get(self::RATE_LIMIT_KEY, 0);

        if ($count >= self::RATE_LIMIT_MAX) {
            Log::channel('simulator')->warning('Binance rate limit approaching, sleeping 1s');
            sleep(1);
        }

        Cache::increment(self::RATE_LIMIT_KEY);

        if ($count === 0) {
            Cache::put(self::RATE_LIMIT_KEY, 1, 60);
        }
    }
}
