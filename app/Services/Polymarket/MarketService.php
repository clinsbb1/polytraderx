<?php

declare(strict_types=1);

namespace App\Services\Polymarket;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketService
{
    private const CRYPTO_ASSETS = ['BTC', 'ETH', 'SOL', 'XRP'];
    private const SHARED_MARKETS_CACHE_KEY = 'polymarket:active_crypto_markets:shared:v1';
    private const SHARED_MARKETS_CACHE_TTL_SECONDS = 10;
    private const MAX_RETRIES = 3;
    private const RETRY_BACKOFF_SECONDS = [1, 2, 4];

    public function __construct(private ?\App\Services\Settings\SettingsService $settings = null)
    {
        $this->settings = $this->settings ?? app(\App\Services\Settings\SettingsService::class);
    }

    public function getActiveCryptoMarkets(?PolymarketClient $client = null, ?int $userId = null): Collection
    {
        try {
            $normalized = Cache::remember(
                self::SHARED_MARKETS_CACHE_KEY,
                now()->addSeconds(self::SHARED_MARKETS_CACHE_TTL_SECONDS),
                fn() => $this->fetchSharedActiveCryptoMarkets()
            );

            if (is_array($normalized)) {
                $normalized = collect($normalized);
            }

            if (!$normalized instanceof Collection) {
                return collect();
            }

            // Filter by user's selected durations if userId provided
            if ($userId !== null) {
                $beforeCount = $normalized->count();
                $allowedDurations = $this->getAllowedDurations($userId);
                $normalized = $normalized->filter(fn(array $market) =>
                    in_array($market['duration'], $allowedDurations)
                );

                if ($beforeCount > 0 && $normalized->isEmpty()) {
                    Log::channel('simulator')->warning('All normalized markets filtered out by user duration selection', [
                        'user_id' => $userId,
                        'allowed_durations' => $allowedDurations,
                        'normalized_before_filter' => $beforeCount,
                    ]);
                }
            }

            return $normalized->values();
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Failed to fetch active crypto markets', [
                'user_id' => $client?->getUserId(),
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    public function getMarketsEndingSoon(?PolymarketClient $client = null, int $withinSeconds = 180, ?int $userId = null): Collection
    {
        return $this->getActiveCryptoMarkets($client, $userId)
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
            if (preg_match('/\b' . preg_quote($asset, '/') . '\b/i', $question) === 1) {
                return $asset;
            }
        }

        // Fallback for compact tickers/slugs where separators are missing (e.g. "BTC5M", "ETHUSD").
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
            'RIPPLE' => 'XRP',
        ];

        foreach ($assetMap as $name => $symbol) {
            if (str_contains($question, $name)) {
                return $symbol;
            }
        }

        return null;
    }

    public function identifyDuration(string $marketQuestion): ?string
    {
        $question = strtoupper($marketQuestion);

        // 5-minute variants: "5 min", "5m", "5-minute", "five minute", etc.
        if (preg_match('/(?:\b5\s*[- ]?\s*M(?:IN(?:UTE)?S?)?\b|\bFIVE\s+MIN(?:UTE)?S?\b)/i', $question) === 1) {
            return '5min';
        }

        // 15-minute variants: "15 min", "15m", "15-minute", "fifteen minute", etc.
        if (preg_match('/(?:\b15\s*[- ]?\s*M(?:IN(?:UTE)?S?)?\b|\bFIFTEEN\s+MIN(?:UTE)?S?\b)/i', $question) === 1) {
            return '15min';
        }

        // Fallback for compact strings (e.g. "BTC5M", "15MINUTE", "M15")
        if (str_contains($question, '5M') || str_contains($question, '5MIN')) {
            return '5min';
        }
        if (str_contains($question, '15M') || str_contains($question, '15MIN')) {
            return '15min';
        }

        return null;
    }

    private function getAllowedDurations(int $userId): array
    {
        $durationsString = $this->settings->getString('MARKET_DURATIONS', '5min,15min', $userId);
        $durations = array_map('trim', explode(',', $durationsString));

        // Validate and filter
        $valid = array_filter($durations, fn($d) => in_array($d, ['5min', '15min']));

        // If empty, default to both
        return !empty($valid) ? $valid : ['5min', '15min'];
    }

    public function getMarketEndTime(array $market): ?Carbon
    {
        $endTime = $market['end_date_iso']
            ?? $market['end_date']
            ?? $market['end_time']
            ?? $market['endTime']
            ?? $market['resolution_time']
            ?? $market['resolutionTime']
            ?? $market['expiration']
            ?? $market['expiresAt']
            ?? $market['endDateIso']
            ?? $market['endDate']
            ?? $market['umaEndDate']
            ?? $market['closedTime']
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

    private function getMarketStartTime(array $market): ?Carbon
    {
        $startTime = $market['start_date_iso']
            ?? $market['start_date']
            ?? $market['start_time']
            ?? $market['startTime']
            ?? $market['startDateIso']
            ?? $market['startDate']
            ?? $market['created_at']
            ?? $market['createdAt']
            ?? null;

        if ($startTime === null) {
            return null;
        }

        try {
            if (is_numeric($startTime)) {
                return Carbon::createFromTimestamp((int) $startTime);
            }
            return Carbon::parse($startTime);
        } catch (\Exception) {
            return null;
        }
    }

    public function parseMarketPrices(array $market): array
    {
        $tokens = $market['tokens'] ?? [];
        if (!is_array($tokens)) {
            $tokens = [];
        }

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

        $outcomePrices = $market['outcomePrices'] ?? null;
        if (is_string($outcomePrices)) {
            $decoded = json_decode($outcomePrices, true);
            $outcomePrices = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($outcomePrices)) {
            $outcomePrices = [];
        }

        $clobTokenIds = $market['clobTokenIds'] ?? [];
        if (is_string($clobTokenIds)) {
            $decoded = json_decode($clobTokenIds, true);
            $clobTokenIds = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($clobTokenIds)) {
            $clobTokenIds = [];
        }

        return [
            'yes_price' => (float) ($yesToken['price'] ?? $market['yes_price'] ?? $market['yesPrice'] ?? $outcomePrices[0] ?? 0),
            'no_price' => (float) ($noToken['price'] ?? $market['no_price'] ?? $market['noPrice'] ?? $outcomePrices[1] ?? 0),
            'yes_token_id' => $yesToken['token_id'] ?? $yesToken['tokenId'] ?? $market['yes_token_id'] ?? $market['yesTokenId'] ?? $clobTokenIds[0] ?? '',
            'no_token_id' => $noToken['token_id'] ?? $noToken['tokenId'] ?? $market['no_token_id'] ?? $market['noTokenId'] ?? $clobTokenIds[1] ?? '',
        ];
    }

    private function fetchSharedActiveCryptoMarkets(): Collection
    {
        $gammaBaseUrl = rtrim((string) config('services.polymarket.gamma_url', 'https://gamma-api.polymarket.com'), '/');
        $clobBaseUrl = rtrim((string) config('services.polymarket.base_url', 'https://clob.polymarket.com'), '/');
        $gammaUrl = $gammaBaseUrl . '/markets';
        $clobUrl = $clobBaseUrl . '/markets';
        $lastException = null;

        // Prefer Gamma API because it carries richer market metadata (question/title/duration cues).
        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout(15)->get($gammaUrl, [
                    'active' => true,
                    'closed' => false,
                    'archived' => false,
                    'limit' => 1000,
                ]);

                if ($response->successful()) {
                    $payload = $response->json();
                    $markets = $payload['data'] ?? $payload;

                    if (!is_array($markets)) {
                        return collect();
                    }

                    $normalized = collect($markets)
                        ->filter(fn($market) => is_array($market))
                        ->map(fn(array $market) => $this->normalizeMarket($market))
                        ->filter(fn(?array $market) => $market !== null)
                        ->values();

                    if (count($markets) > 0 && $normalized->isEmpty()) {
                        $samples = collect($markets)->take(3)->map(function ($m) {
                            if (!is_array($m)) {
                                return '';
                            }
                            return (string) ($m['question'] ?? $m['title'] ?? $m['name'] ?? $m['slug'] ?? '');
                        })->values()->toArray();
                        $dropReasons = $this->summarizeNormalizationDropReasons($markets);

                        Log::channel('simulator')->warning('Polymarket gamma fetch returned markets but none normalized', [
                            'raw_count' => count($markets),
                            'normalized_count' => 0,
                            'sample_titles' => $samples,
                            'drop_reasons' => $dropReasons,
                        ]);
                    }

                    return $normalized;
                }

                $status = $response->status();

                if ($status === 429 || $status >= 500) {
                    $sleepFor = (int) ($response->header('Retry-After') ?: (self::RETRY_BACKOFF_SECONDS[$attempt] ?? 2));
                    Log::channel('simulator')->warning('Shared Polymarket market fetch throttled or failed', [
                        'status' => $status,
                        'attempt' => $attempt + 1,
                        'retry_after' => $sleepFor,
                    ]);

                    if ($attempt < self::MAX_RETRIES - 1) {
                        sleep(max(1, $sleepFor));
                        continue;
                    }
                }

                Log::channel('simulator')->warning('Shared Polymarket market fetch returned non-success response', [
                    'status' => $status,
                    'body' => $response->body(),
                ]);

                return collect();
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < self::MAX_RETRIES - 1) {
                    $sleepFor = self::RETRY_BACKOFF_SECONDS[$attempt] ?? 2;
                    sleep(max(1, $sleepFor));
                    continue;
                }
            }
        }

        // Fallback to CLOB API if Gamma is unavailable.
        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout(15)->get($clobUrl, [
                    'active' => 'true',
                ]);

                if ($response->successful()) {
                    $payload = $response->json();
                    $markets = $payload['data'] ?? $payload;

                    if (!is_array($markets)) {
                        return collect();
                    }

                    $normalized = collect($markets)
                        ->filter(fn($market) => is_array($market))
                        ->map(fn(array $market) => $this->normalizeMarket($market))
                        ->filter(fn(?array $market) => $market !== null)
                        ->values();

                    if (count($markets) > 0 && $normalized->isEmpty()) {
                        $samples = collect($markets)->take(3)->map(function ($m) {
                            if (!is_array($m)) {
                                return '';
                            }
                            return (string) ($m['question'] ?? $m['title'] ?? $m['name'] ?? $m['slug'] ?? '');
                        })->values()->toArray();
                        $dropReasons = $this->summarizeNormalizationDropReasons($markets);

                        Log::channel('simulator')->warning('Polymarket clob fetch returned markets but none normalized', [
                            'raw_count' => count($markets),
                            'normalized_count' => 0,
                            'sample_titles' => $samples,
                            'drop_reasons' => $dropReasons,
                        ]);
                    }

                    return $normalized;
                }

                $status = $response->status();
                if (($status === 429 || $status >= 500) && $attempt < self::MAX_RETRIES - 1) {
                    $sleepFor = (int) ($response->header('Retry-After') ?: (self::RETRY_BACKOFF_SECONDS[$attempt] ?? 2));
                    sleep(max(1, $sleepFor));
                    continue;
                }

                return collect();
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < self::MAX_RETRIES - 1) {
                    $sleepFor = self::RETRY_BACKOFF_SECONDS[$attempt] ?? 2;
                    sleep(max(1, $sleepFor));
                    continue;
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return collect();
    }

    private function normalizeMarket(array $market): ?array
    {
        $question = (string) ($market['question']
            ?? $market['market_question']
            ?? $market['title']
            ?? $market['name']
            ?? '');

        // Use a broader text corpus so minor API wording changes don't zero out valid markets.
        $textCorpus = $this->buildDetectionText($market);
        $asset = $this->identifyAsset($textCorpus);

        if ($asset === null) {
            return null;
        }

        $duration = $this->resolveMarketDuration($market, $textCorpus);

        if ($duration === null) {
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

        // Guardrail: simulation scans short rounds only.
        if ($secondsRemaining > 1200) {
            return null;
        }

        $prices = $this->parseMarketPrices($market);

        return [
            'condition_id' => $market['condition_id'] ?? $market['conditionId'] ?? $market['id'] ?? '',
            'question' => $question,
            'slug' => $market['slug'] ?? $market['market_slug'] ?? '',
            'asset' => $asset,
            'duration' => $duration,
            'yes_token_id' => $prices['yes_token_id'],
            'no_token_id' => $prices['no_token_id'],
            'yes_price' => $prices['yes_price'],
            'no_price' => $prices['no_price'],
            'volume' => (float) ($market['volume'] ?? $market['volumeNum'] ?? 0),
            'end_time' => $endTime,
            'seconds_remaining' => $secondsRemaining,
        ];
    }

    private function buildDetectionText(array $market): string
    {
        $parts = [
            $market['question'] ?? null,
            $market['market_question'] ?? null,
            $market['title'] ?? null,
            $market['name'] ?? null,
            $market['slug'] ?? null,
            $market['market_slug'] ?? null,
            $market['description'] ?? null,
            $market['subtitle'] ?? null,
            $market['ticker'] ?? null,
            $market['marketType'] ?? null,
            $market['market_type'] ?? null,
        ];

        // Include nested event metadata because Gamma often stores useful descriptors there.
        $events = $market['events'] ?? [];
        if (is_array($events)) {
            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }
                $parts[] = $event['title'] ?? null;
                $parts[] = $event['slug'] ?? null;
                $parts[] = $event['ticker'] ?? null;
                $parts[] = $event['description'] ?? null;
            }
        }

        $outcomes = $market['outcomes'] ?? null;
        if (is_string($outcomes)) {
            $decoded = json_decode($outcomes, true);
            if (is_array($decoded)) {
                $parts = array_merge($parts, $decoded);
            }
        } elseif (is_array($outcomes)) {
            $parts = array_merge($parts, $outcomes);
        }

        return strtoupper(implode(' ', array_filter(array_map(
            fn($v) => is_scalar($v) ? (string) $v : '',
            $parts
        ))));
    }

    private function resolveMarketDuration(array $market, string $textCorpus): ?string
    {
        // Structured fields first (safer than free-text when available).
        $candidates = [
            $market['duration'] ?? null,
            $market['interval'] ?? null,
            $market['timeframe'] ?? null,
            $market['marketType'] ?? null,
            $market['market_type'] ?? null,
            $market['category'] ?? null,
            $market['type'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }
            $detected = $this->identifyDuration((string) $candidate);
            if ($detected !== null) {
                return $detected;
            }
        }

        $fromText = $this->identifyDuration($textCorpus);
        if ($fromText !== null) {
            return $fromText;
        }

        // If text is ambiguous, infer using near-term close timing only.
        $end = $this->getMarketEndTime($market);
        if ($end !== null) {
            $remaining = (int) now()->diffInSeconds($end, false);
            if ($remaining > 0 && $remaining <= 420) {
                return '5min';
            }
            if ($remaining > 420 && $remaining <= 1200) {
                return '15min';
            }
        }

        return null;
    }

    private function summarizeNormalizationDropReasons(array $markets): array
    {
        $stats = [
            'non_array' => 0,
            'asset_not_detected' => 0,
            'duration_not_detected' => 0,
            'end_time_missing' => 0,
            'already_expired' => 0,
            'ok' => 0,
        ];

        foreach (array_slice($markets, 0, 200) as $market) {
            if (!is_array($market)) {
                $stats['non_array']++;
                continue;
            }

            $text = $this->buildDetectionText($market);

            if ($this->identifyAsset($text) === null) {
                $stats['asset_not_detected']++;
                continue;
            }

            if ($this->resolveMarketDuration($market, $text) === null) {
                $stats['duration_not_detected']++;
                continue;
            }

            $endTime = $this->getMarketEndTime($market);
            if ($endTime === null) {
                $stats['end_time_missing']++;
                continue;
            }

            if ((int) now()->diffInSeconds($endTime, false) < 0) {
                $stats['already_expired']++;
                continue;
            }

            $stats['ok']++;
        }

        return $stats;
    }
}
