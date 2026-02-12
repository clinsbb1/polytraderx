<?php

declare(strict_types=1);

namespace App\Services\Polymarket;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MarketService
{
    private const CRYPTO_ASSETS = ['BTC', 'ETH', 'SOL'];

    public function getActiveCryptoMarkets(PolymarketClient $client): Collection
    {
        try {
            $response = $client->get('/markets', [
                'active' => 'true',
            ]);

            $markets = $response['data'] ?? $response;

            if (!is_array($markets)) {
                return collect();
            }

            return collect($markets)
                ->map(fn(array $market) => $this->normalizeMarket($market))
                ->filter(fn(?array $market) => $market !== null)
                ->values();
        } catch (\Exception $e) {
            Log::channel('bot')->error('Failed to fetch active crypto markets', [
                'user_id' => $client->getUserId(),
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    public function getMarketsEndingSoon(PolymarketClient $client, int $withinSeconds = 180): Collection
    {
        return $this->getActiveCryptoMarkets($client)
            ->filter(fn(array $market) => $market['seconds_remaining'] <= $withinSeconds && $market['seconds_remaining'] > 0)
            ->sortBy('seconds_remaining')
            ->values();
    }

    public function getMarketDetails(PolymarketClient $client, string $conditionId): array
    {
        return $client->get("/markets/{$conditionId}");
    }

    public function getOrderBook(PolymarketClient $client, string $tokenId): array
    {
        $response = $client->get('/book', ['token_id' => $tokenId]);

        $bids = $response['bids'] ?? [];
        $asks = $response['asks'] ?? [];

        $bestBid = !empty($bids) ? (float) $bids[0]['price'] : 0.0;
        $bestAsk = !empty($asks) ? (float) $asks[0]['price'] : 0.0;
        $midpoint = ($bestBid + $bestAsk) / 2;
        $spread = $bestAsk - $bestBid;

        return [
            'bids' => $bids,
            'asks' => $asks,
            'best_bid' => $bestBid,
            'best_ask' => $bestAsk,
            'midpoint' => $midpoint,
            'spread' => $spread,
        ];
    }

    public function identifyAsset(string $marketQuestion): ?string
    {
        $question = strtoupper($marketQuestion);

        foreach (self::CRYPTO_ASSETS as $asset) {
            if (str_contains($question, $asset)) {
                return $asset;
            }
        }

        // Also check full names
        $assetMap = [
            'BITCOIN' => 'BTC',
            'ETHEREUM' => 'ETH',
            'SOLANA' => 'SOL',
        ];

        foreach ($assetMap as $name => $symbol) {
            if (str_contains($question, $name)) {
                return $symbol;
            }
        }

        return null;
    }

    public function getMarketEndTime(array $market): ?Carbon
    {
        $endTime = $market['end_date_iso']
            ?? $market['end_time']
            ?? $market['resolution_time']
            ?? $market['expiration']
            ?? null;

        if ($endTime === null) {
            return null;
        }

        try {
            if (is_numeric($endTime)) {
                return Carbon::createFromTimestamp((int) $endTime);
            }
            return Carbon::parse($endTime);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function parseMarketPrices(array $market): array
    {
        $tokens = $market['tokens'] ?? [];

        $yesToken = null;
        $noToken = null;

        foreach ($tokens as $token) {
            $outcome = strtoupper($token['outcome'] ?? '');
            if ($outcome === 'YES') {
                $yesToken = $token;
            } elseif ($outcome === 'NO') {
                $noToken = $token;
            }
        }

        return [
            'yes_price' => (float) ($yesToken['price'] ?? $market['yes_price'] ?? $market['outcomePrices'][0] ?? 0),
            'no_price' => (float) ($noToken['price'] ?? $market['no_price'] ?? $market['outcomePrices'][1] ?? 0),
            'yes_token_id' => $yesToken['token_id'] ?? $market['yes_token_id'] ?? $market['clobTokenIds'][0] ?? '',
            'no_token_id' => $noToken['token_id'] ?? $market['no_token_id'] ?? $market['clobTokenIds'][1] ?? '',
        ];
    }

    private function normalizeMarket(array $market): ?array
    {
        $question = $market['question'] ?? $market['market_question'] ?? '';
        $asset = $this->identifyAsset($question);

        if ($asset === null) {
            return null;
        }

        $endTime = $this->getMarketEndTime($market);

        if ($endTime === null) {
            return null;
        }

        $secondsRemaining = (int) now()->diffInSeconds($endTime, false);

        if ($secondsRemaining < 0) {
            return null;
        }

        $prices = $this->parseMarketPrices($market);

        return [
            'condition_id' => $market['condition_id'] ?? $market['id'] ?? '',
            'question' => $question,
            'slug' => $market['slug'] ?? $market['market_slug'] ?? '',
            'asset' => $asset,
            'yes_token_id' => $prices['yes_token_id'],
            'no_token_id' => $prices['no_token_id'],
            'yes_price' => $prices['yes_price'],
            'no_price' => $prices['no_price'],
            'volume' => (float) ($market['volume'] ?? $market['volumeNum'] ?? 0),
            'end_time' => $endTime,
            'seconds_remaining' => $secondsRemaining,
        ];
    }
}
