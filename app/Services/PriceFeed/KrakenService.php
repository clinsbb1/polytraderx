<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KrakenService
{
    private string $baseUrl;

    private const ASSET_PAIRS = [
        'BTC' => 'XBTUSD',
        'ETH' => 'ETHUSD',
        'SOL' => 'SOLUSD',
        'XRP' => 'XRPUSD',
    ];

    private const CACHE_TTL_SECONDS = 3;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.kraken.base_url', 'https://api.kraken.com/0/public'), '/');
    }

    public function getPriceForAsset(string $asset): float
    {
        $pair = $this->resolvePair($asset);
        $cacheKey = "kraken:price:{$pair}";

        return (float) Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($pair) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/Ticker", [
                'pair' => $pair,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Kraken ticker error ({$response->status()}): {$response->body()}");
            }

            $json = $response->json();
            $result = is_array($json['result'] ?? null) ? $json['result'] : [];
            $row = $this->firstResultRow($result);
            $last = $row['c'][0] ?? null;

            if ($last === null) {
                throw new \RuntimeException("Kraken ticker payload missing last price for pair {$pair}");
            }

            return (float) $last;
        });
    }

    public function getPriceChange(string $asset, int $minutes = 15): array
    {
        $pair = $this->resolvePair($asset);
        $cacheKey = "kraken:ohlc:{$pair}";

        $rows = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($pair) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/OHLC", [
                'pair' => $pair,
                'interval' => 1,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Kraken OHLC error ({$response->status()}): {$response->body()}");
            }

            $json = $response->json();
            $result = is_array($json['result'] ?? null) ? $json['result'] : [];
            unset($result['last']);
            $series = $this->firstResultRow($result);

            if (!is_array($series)) {
                return [];
            }

            return array_values(array_filter($series, fn($row) => is_array($row) && count($row) >= 5));
        });

        if (empty($rows)) {
            $current = $this->getPriceForAsset($asset);
            return [
                'open' => $current,
                'current' => $current,
                'change_pct' => 0.0,
                'high' => $current,
                'low' => $current,
            ];
        }

        $needed = max(2, $minutes + 1);
        $candles = array_slice($rows, -$needed);
        $first = $candles[0];
        $last = $candles[count($candles) - 1];
        // [time, open, high, low, close, ...]
        $openPrice = (float) ($first[1] ?? 0);
        $currentPrice = (float) ($last[4] ?? 0);
        $high = max(array_map(fn($c) => (float) ($c[2] ?? 0), $candles));
        $low = min(array_map(fn($c) => (float) ($c[3] ?? 0), $candles));
        $changePct = $openPrice > 0 ? (($currentPrice - $openPrice) / $openPrice) * 100 : 0.0;

        return [
            'open' => $openPrice,
            'current' => $currentPrice,
            'change_pct' => round($changePct, 4),
            'high' => $high,
            'low' => $low,
        ];
    }

    private function firstResultRow(array $result): array
    {
        foreach ($result as $value) {
            if (is_array($value)) {
                return $value;
            }
        }

        return [];
    }

    private function resolvePair(string $asset): string
    {
        $pair = self::ASSET_PAIRS[strtoupper($asset)] ?? null;
        if ($pair === null) {
            throw new \InvalidArgumentException("Unsupported asset for Kraken: {$asset}");
        }

        return $pair;
    }
}

