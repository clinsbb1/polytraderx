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
    private const SHARED_MARKETS_CACHE_KEY = 'polymarket:active_crypto_markets:shared:v2';
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

                $afterDurationCount = $normalized->count();
                $allowedAssets = $this->getAllowedAssets($userId);
                $normalized = $normalized->filter(fn(array $market) =>
                    in_array((string) ($market['asset'] ?? ''), $allowedAssets, true)
                );

                if ($afterDurationCount > 0 && $normalized->isEmpty()) {
                    Log::channel('simulator')->warning('All normalized markets filtered out by user asset selection', [
                        'user_id' => $userId,
                        'allowed_assets' => $allowedAssets,
                        'normalized_before_filter' => $afterDurationCount,
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

    public function diagnoseActiveCryptoMarkets(int $sampleLimit = 5): array
    {
        $sampleLimit = max(1, min(20, $sampleLimit));

        $result = $this->fetchRawActiveMarketsForDiagnostics();
        $markets = $result['markets'];

        $report = [
            'source' => $result['source'],
            'raw_count' => count($markets),
            'normalized_count' => 0,
            'rejected_count' => 0,
            'duration_breakdown' => ['5min' => 0, '15min' => 0],
            'asset_breakdown' => ['BTC' => 0, 'ETH' => 0, 'SOL' => 0, 'XRP' => 0],
            'rejection_breakdown' => [],
            'accepted_samples' => [],
            'rejected_samples' => [],
            'gamma_status' => $result['gamma_status'],
            'clob_status' => $result['clob_status'],
            'http_error' => $result['http_error'],
        ];

        foreach ($markets as $market) {
            if (!is_array($market)) {
                $report['rejected_count']++;
                $report['rejection_breakdown']['non_array'] = ($report['rejection_breakdown']['non_array'] ?? 0) + 1;
                continue;
            }

            [$normalized, $reason] = $this->normalizeMarketWithReason($market);

            if ($normalized === null) {
                $reason = $reason ?? 'rejected_unknown';
                $report['rejected_count']++;
                $report['rejection_breakdown'][$reason] = ($report['rejection_breakdown'][$reason] ?? 0) + 1;

                if (count($report['rejected_samples']) < $sampleLimit) {
                    $report['rejected_samples'][] = [
                        'reason' => $reason,
                        'slug' => (string) ($market['slug'] ?? $market['market_slug'] ?? ''),
                        'question' => (string) ($market['question'] ?? $market['title'] ?? $market['name'] ?? ''),
                    ];
                }

                continue;
            }

            $report['normalized_count']++;
            $duration = (string) ($normalized['duration'] ?? '');
            if (isset($report['duration_breakdown'][$duration])) {
                $report['duration_breakdown'][$duration]++;
            }

            $asset = (string) ($normalized['asset'] ?? '');
            if (isset($report['asset_breakdown'][$asset])) {
                $report['asset_breakdown'][$asset]++;
            }

            if (count($report['accepted_samples']) < $sampleLimit) {
                $report['accepted_samples'][] = [
                    'asset' => $asset,
                    'duration' => $duration,
                    'slug' => (string) ($normalized['slug'] ?? ''),
                    'question' => (string) ($normalized['question'] ?? ''),
                    'seconds_remaining' => (int) ($normalized['seconds_remaining'] ?? 0),
                ];
            }
        }

        arsort($report['rejection_breakdown']);

        return $report;
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

    private function getAllowedAssets(int $userId): array
    {
        $assetsString = $this->settings->getString('MONITORED_ASSETS', 'BTC,ETH,SOL,XRP', $userId);
        $assets = array_map(
            fn($asset) => strtoupper(trim((string) $asset)),
            explode(',', $assetsString)
        );

        $valid = array_values(array_filter(
            array_unique($assets),
            fn($asset) => in_array($asset, self::CRYPTO_ASSETS, true)
        ));

        return !empty($valid) ? $valid : self::CRYPTO_ASSETS;
    }

    public function getMarketEndTime(array $market): ?Carbon
    {
        $candidates = [
            $market['end_date_iso'] ?? null,
            $market['endDateIso'] ?? null,
            $market['end_date'] ?? null,
            $market['endDate'] ?? null,
            $market['end_time'] ?? null,
            $market['endTime'] ?? null,
            $market['resolution_time'] ?? null,
            $market['resolutionTime'] ?? null,
            $market['expiration'] ?? null,
            $market['expiresAt'] ?? null,
            $market['umaEndDate'] ?? null,
            $market['closedTime'] ?? null,
            $market['upperBoundDate'] ?? null,
            $market['gameStartTime'] ?? null,
        ];

        $events = $market['events'] ?? [];
        if (is_array($events)) {
            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }
                $candidates[] = $event['endDate'] ?? null;
                $candidates[] = $event['endDateIso'] ?? null;
                $candidates[] = $event['end_date_iso'] ?? null;
                $candidates[] = $event['umaEndDate'] ?? null;
                $candidates[] = $event['gameStartTime'] ?? null;
                $candidates[] = $event['upperBoundDate'] ?? null;
            }
        }

        $firstParsed = null;
        $nearestFuture = null;

        foreach ($candidates as $candidate) {
            $parsed = $this->parseTimeCandidate($candidate);
            if (!$parsed instanceof Carbon) {
                continue;
            }

            $firstParsed ??= $parsed;

            if ($parsed->greaterThanOrEqualTo(now())) {
                if ($nearestFuture === null || $parsed->lessThan($nearestFuture)) {
                    $nearestFuture = $parsed;
                }
            }
        }

        return $nearestFuture ?? $firstParsed;
    }

    private function getMarketStartTime(array $market): ?Carbon
    {
        $candidates = [
            $market['start_date_iso'] ?? null,
            $market['startDateIso'] ?? null,
            $market['start_date'] ?? null,
            $market['startDate'] ?? null,
            $market['start_time'] ?? null,
            $market['startTime'] ?? null,
            $market['created_at'] ?? null,
            $market['createdAt'] ?? null,
            $market['lowerBoundDate'] ?? null,
        ];

        $events = $market['events'] ?? [];
        if (is_array($events)) {
            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }
                $candidates[] = $event['startDate'] ?? null;
                $candidates[] = $event['startDateIso'] ?? null;
                $candidates[] = $event['start_date_iso'] ?? null;
                $candidates[] = $event['createdAt'] ?? null;
                $candidates[] = $event['created_at'] ?? null;
                $candidates[] = $event['lowerBoundDate'] ?? null;
            }
        }

        $parsed = collect($candidates)
            ->map(fn($value) => $this->parseTimeCandidate($value))
            ->filter(fn($value) => $value instanceof Carbon)
            ->sortBy(fn(Carbon $dt) => $dt->getTimestamp())
            ->values();

        return $parsed->first();
    }

    private function parseTimeCandidate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === null || !is_scalar($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $timestamp = (float) $value;
                // Handle milliseconds when provided by upstream APIs.
                if ($timestamp > 1000000000000) {
                    $timestamp = (int) floor($timestamp / 1000);
                }
                return Carbon::createFromTimestamp((int) $timestamp);
            }

            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                return null;
            }

            return Carbon::parse($trimmed);
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
            if ($outcome === 'YES' || $outcome === 'UP') {
                $yesToken = $token;
            } elseif ($outcome === 'NO' || $outcome === 'DOWN') {
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
        $gammaMarketsUrl = $gammaBaseUrl . '/markets';
        $lastException = null;

        // Speed-series crypto markets (5M/15M) are NOT returned by tag_slug=crypto.
        // They are only available via the /markets endpoint with question_contains filter,
        // and their slugs follow: {asset}-updown-{5m|15m}-{timestamp}
        $slugPattern = '/^(btc|eth|sol|xrp)-updown-(5m|15m)-\d+$/';

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout(15)->get($gammaMarketsUrl, [
                    'active' => true,
                    'closed' => false,
                    'order' => 'startDate',
                    'ascending' => false,
                    'question_contains' => 'Up or Down',
                    'limit' => 200,
                ]);

                if ($response->successful()) {
                    $payload = $response->json();
                    $markets = $payload['data'] ?? $payload;

                    if (!is_array($markets)) {
                        return collect();
                    }

                    // Pre-filter by slug to ensure only crypto speed-series markets pass.
                    $speedSeries = collect($markets)
                        ->filter(fn($market) => is_array($market))
                        ->filter(fn(array $market) => preg_match(
                            $slugPattern,
                            (string) ($market['slug'] ?? $market['market_slug'] ?? '')
                        ) === 1);

                    $normalized = $speedSeries
                        ->map(fn(array $market) => $this->normalizeMarket($market))
                        ->filter(fn(?array $market) => $market !== null)
                        ->values();

                    if ($speedSeries->isNotEmpty() && $normalized->isEmpty()) {
                        $samples = $speedSeries->take(3)->map(function (array $m) {
                            return (string) ($m['question'] ?? $m['title'] ?? $m['slug'] ?? '');
                        })->values()->toArray();
                        $dropReasons = $this->summarizeNormalizationDropReasons($speedSeries->values()->toArray());

                        Log::channel('simulator')->warning('Polymarket speed-series fetch: markets found but none normalized', [
                            'raw_count' => count($markets),
                            'slug_matched' => $speedSeries->count(),
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
                    Log::channel('simulator')->warning('Polymarket speed-series fetch throttled or failed', [
                        'status' => $status,
                        'attempt' => $attempt + 1,
                        'retry_after' => $sleepFor,
                    ]);

                    if ($attempt < self::MAX_RETRIES - 1) {
                        sleep(max(1, $sleepFor));
                        continue;
                    }
                }

                Log::channel('simulator')->warning('Polymarket speed-series fetch returned non-success response', [
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

        if ($lastException) {
            throw $lastException;
        }

        return collect();
    }

    private function normalizeMarket(array $market): ?array
    {
        [$normalized, ] = $this->normalizeMarketWithReason($market);

        return $normalized;
    }

    private function normalizeMarketWithReason(array $market): array
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
            return [null, 'asset_not_detected'];
        }

        // Hard requirement: only keep dedicated Crypto 5M/15M series markets.
        // Do not infer eligibility from "time remaining" on unrelated markets.
        if (!$this->isCryptoSpeedSeriesMarket($market, $textCorpus)) {
            return [null, 'not_crypto_5m_15m_series'];
        }

        $duration = $this->resolveMarketDuration($market, $textCorpus);

        if ($duration === null) {
            return [null, 'duration_not_detected'];
        }

        $endTime = $this->getMarketEndTime($market);

        if ($endTime === null) {
            return [null, 'end_time_missing'];
        }

        $intervalSeconds = $this->resolveMarketIntervalSeconds($market, $textCorpus, $duration);
        if ($intervalSeconds !== null) {
            $periodicEnd = $this->computeNextPeriodicClose($market, $endTime, $intervalSeconds);
            if ($periodicEnd !== null) {
                $endTime = $periodicEnd;
            }
        }

        $secondsRemaining = (int) now()->diffInSeconds($endTime, false);

        if ($secondsRemaining < 0) {
            return [null, 'already_expired'];
        }

        // Guardrail: if remaining time is still very large, this is likely not an intraday 5/15m round.
        if ($secondsRemaining > 7200) {
            return [null, 'outside_intraday_window'];
        }

        $prices = $this->parseMarketPrices($market);

        if ($prices['yes_price'] <= 0.0 || $prices['no_price'] <= 0.0) {
            return [null, 'invalid_prices'];
        }

        return [[
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
        ], null];
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

        // Include tags/topics metadata for stronger series detection.
        $parts = array_merge($parts, $this->extractTagStrings($market['tags'] ?? null));
        $parts = array_merge($parts, $this->extractTagStrings($market['topics'] ?? null));
        $parts = array_merge($parts, $this->extractTagStrings($market['categories'] ?? null));

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

    private function extractTagStrings(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                return [$raw];
            }
        }

        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_scalar($item)) {
                $out[] = (string) $item;
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $out[] = (string) ($item['name'] ?? '');
            $out[] = (string) ($item['slug'] ?? '');
            $out[] = (string) ($item['label'] ?? '');
            $out[] = (string) ($item['title'] ?? '');
        }

        return array_values(array_filter($out, fn(string $v) => trim($v) !== ''));
    }

    private function isCryptoSpeedSeriesMarket(array $market, string $textCorpus): bool
    {
        $hasExplicitCryptoSpeedMarker = false;

        $directCandidates = [
            strtoupper((string) ($market['slug'] ?? '')),
            strtoupper((string) ($market['market_slug'] ?? '')),
            strtoupper((string) ($market['seriesSlug'] ?? '')),
            strtoupper((string) ($market['series_slug'] ?? '')),
            strtoupper((string) ($market['category'] ?? '')),
            strtoupper((string) ($market['marketType'] ?? '')),
            strtoupper((string) ($market['market_type'] ?? '')),
        ];

        foreach ($this->extractTagStrings($market['tags'] ?? null) as $tag) {
            $directCandidates[] = strtoupper($tag);
        }
        foreach ($this->extractTagStrings($market['topics'] ?? null) as $tag) {
            $directCandidates[] = strtoupper($tag);
        }
        foreach ($this->extractTagStrings($market['categories'] ?? null) as $tag) {
            $directCandidates[] = strtoupper($tag);
        }

        $events = $market['events'] ?? [];
        if (is_array($events)) {
            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }
                $directCandidates[] = strtoupper((string) ($event['slug'] ?? ''));
                $directCandidates[] = strtoupper((string) ($event['title'] ?? ''));
                $directCandidates[] = strtoupper((string) ($event['category'] ?? ''));
                $directCandidates[] = strtoupper((string) ($event['ticker'] ?? ''));
                $directCandidates[] = strtoupper((string) ($event['seriesSlug'] ?? ''));
                $directCandidates[] = strtoupper((string) ($event['series_slug'] ?? ''));
            }
        }

        $patterns = [
            '/\/CRYPTO\/(?:5M|15M)\b/',
            '/\bCRYPTO[\s_\-\/]*(?:5M|15M|5\s*MIN(?:UTE)?S?|15\s*MIN(?:UTE)?S?)\b/',
            '/\b(?:5M|15M|5\s*MIN(?:UTE)?S?|15\s*MIN(?:UTE)?S?)[\s_\-\/]*CRYPTO\b/',
            '/\b(?:BTC|ETH|SOL|XRP)[\s_\-]+UP[\s_\-]+OR[\s_\-]+DOWN[\s_\-]+(?:5M|15M)\b/',
            '/\b(?:BTC|ETH|SOL|XRP)[\s_\-]*UPDOWN[\s_\-]*(?:5M|15M)\b/',
            '/\b(?:BTC|ETH|SOL|XRP)-UP-OR-DOWN-(?:5M|15M)\b/',
            '/\b(?:BTC|ETH|SOL|XRP)-UPDOWN-(?:5M|15M)\b/',
        ];

        foreach ($directCandidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $candidate) === 1) {
                    $hasExplicitCryptoSpeedMarker = true;
                    break 2;
                }
            }
        }

        // Secondary fallback only if metadata is sparse.
        // Keep strict by requiring both "crypto" and 5M/15M in close proximity.
        if (!$hasExplicitCryptoSpeedMarker && trim($textCorpus) !== '') {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $textCorpus) === 1) {
                    $hasExplicitCryptoSpeedMarker = true;
                    break;
                }
            }
        }

        return $hasExplicitCryptoSpeedMarker;
    }

    private function resolveMarketDuration(array $market, string $textCorpus): ?string
    {
        foreach ($this->collectDurationHints($market) as $candidate) {
            $detected = $this->detectDurationFromHint($candidate);
            if ($detected !== null) {
                return $detected;
            }
        }

        $fromText = $this->identifyDuration($textCorpus);
        if ($fromText !== null) {
            return $fromText;
        }

        // No time-based fallback: we only accept explicit 5M/15M series markers.
        return null;
    }

    private function resolveMarketIntervalSeconds(array $market, string $textCorpus, ?string $resolvedDuration = null): ?int
    {
        foreach ($this->collectDurationHints($market) as $candidate) {
            $seconds = $this->detectIntervalSecondsFromHint($candidate);
            if ($seconds !== null) {
                return $seconds;
            }
        }

        $fromText = $this->identifyDuration($textCorpus);
        if ($fromText === '5min') {
            return 300;
        }
        if ($fromText === '15min') {
            return 900;
        }

        return match ($resolvedDuration) {
            '5min' => 300,
            '15min' => 900,
            default => null,
        };
    }

    private function computeNextPeriodicClose(array $market, Carbon $hardEnd, int $intervalSeconds): ?Carbon
    {
        if ($intervalSeconds <= 0) {
            return null;
        }

        $now = now();

        if ($hardEnd->lessThanOrEqualTo($now)) {
            return null;
        }

        $anchor = $this->getMarketStartTime($market);

        // Fallback anchor aligned to minute clock.
        if (!$anchor instanceof Carbon) {
            $anchor = $now->copy()->startOfMinute();
        }

        if ($anchor->greaterThan($now)) {
            $candidate = $anchor->copy();
        } else {
            $elapsed = max(0, $now->getTimestamp() - $anchor->getTimestamp());
            $steps = intdiv($elapsed, $intervalSeconds) + 1;
            $candidate = $anchor->copy()->addSeconds($steps * $intervalSeconds);
        }

        if ($candidate->lessThanOrEqualTo($now)) {
            $candidate = $candidate->copy()->addSeconds($intervalSeconds);
        }

        if ($candidate->greaterThan($hardEnd)) {
            // If next periodic boundary is outside market hard-end, use hard-end.
            return $hardEnd->greaterThan($now) ? $hardEnd : null;
        }

        return $candidate;
    }

    private function collectDurationHints(array $market): array
    {
        $hints = [
            $market['secondsDelay'] ?? null,
            $market['seconds_delay'] ?? null,
            $market['duration'] ?? null,
            $market['interval'] ?? null,
            $market['timeframe'] ?? null,
            $market['frequency'] ?? null,
            $market['resolution'] ?? null,
            $market['marketType'] ?? null,
            $market['market_type'] ?? null,
            $market['category'] ?? null,
            $market['type'] ?? null,
            $market['groupItemTitle'] ?? null,
            $market['ticker'] ?? null,
            $market['slug'] ?? null,
            $market['market_slug'] ?? null,
        ];

        $events = $market['events'] ?? [];
        if (is_array($events)) {
            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }

                $hints[] = $event['title'] ?? null;
                $hints[] = $event['slug'] ?? null;
                $hints[] = $event['ticker'] ?? null;
                $hints[] = $event['category'] ?? null;
                $hints[] = $event['secondsDelay'] ?? null;
                $hints[] = $event['seconds_delay'] ?? null;

                $series = $event['series'] ?? [];
                if (is_array($series)) {
                    foreach ($series as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $hints[] = $item['recurrence'] ?? null;
                        $hints[] = $item['seriesType'] ?? null;
                        $hints[] = $item['interval'] ?? null;
                        $hints[] = $item['duration'] ?? null;
                        $hints[] = $item['secondsDelay'] ?? null;
                        $hints[] = $item['seconds_delay'] ?? null;
                        $hints[] = $item['title'] ?? null;
                        $hints[] = $item['slug'] ?? null;
                    }
                }
            }
        }

        return $hints;
    }

    private function detectDurationFromHint(mixed $hint): ?string
    {
        $seconds = $this->detectIntervalSecondsFromHint($hint);
        if ($seconds === 300) {
            return '5min';
        }
        if ($seconds === 900) {
            return '15min';
        }

        if ($hint === null || (is_string($hint) && trim($hint) === '')) {
            return null;
        }

        if (!is_scalar($hint)) {
            return null;
        }

        return $this->identifyDuration((string) $hint);
    }

    private function detectIntervalSecondsFromHint(mixed $hint): ?int
    {
        if ($hint === null || (is_string($hint) && trim($hint) === '')) {
            return null;
        }

        if (is_numeric($hint)) {
            $value = (float) $hint;

            // Seconds-like values.
            if ($value >= 150 && $value <= 600) {
                return 300;
            }
            if ($value >= 600 && $value <= 1500) {
                return 900;
            }

            // Minutes-like values.
            if ($value >= 4 && $value <= 6) {
                return 300;
            }
            if ($value >= 12 && $value <= 18) {
                return 900;
            }

            return null;
        }

        if (!is_scalar($hint)) {
            return null;
        }

        $text = strtoupper((string) $hint);

        if (preg_match('/(?:\b300\b|PT5M|\b5\s*MIN(?:UTE)?S?\b|\bEVERY\s*5\b|\bM5\b)/i', $text) === 1) {
            return 300;
        }
        if (preg_match('/(?:\b900\b|PT15M|\b15\s*MIN(?:UTE)?S?\b|\bEVERY\s*15\b|\bM15\b)/i', $text) === 1) {
            return 900;
        }

        return null;
    }

    private function summarizeNormalizationDropReasons(array $markets): array
    {
        $stats = [
            'non_array' => 0,
            'ok' => 0,
        ];

        foreach (array_slice($markets, 0, 200) as $market) {
            if (!is_array($market)) {
                $stats['non_array']++;
                continue;
            }

            [$normalized, $reason] = $this->normalizeMarketWithReason($market);
            if ($normalized === null) {
                $key = $reason ?? 'rejected_unknown';
                $stats[$key] = ($stats[$key] ?? 0) + 1;
                continue;
            }

            $stats['ok']++;
        }

        return $stats;
    }

    private function fetchRawActiveMarketsForDiagnostics(): array
    {
        $gammaBaseUrl = rtrim((string) config('services.polymarket.gamma_url', 'https://gamma-api.polymarket.com'), '/');

        $gammaStatus = null;
        $lastError = null;

        try {
            $response = Http::timeout(15)->get($gammaBaseUrl . '/markets', [
                'active' => true,
                'closed' => false,
                'order' => 'startDate',
                'ascending' => false,
                'question_contains' => 'Up or Down',
                'limit' => 200,
            ]);

            $gammaStatus = $response->status();
            if ($response->successful()) {
                $payload = $response->json();
                $markets = $payload['data'] ?? $payload;

                return [
                    'source' => 'gamma_markets',
                    'markets' => is_array($markets) ? $markets : [],
                    'gamma_status' => $gammaStatus,
                    'clob_status' => null,
                    'http_error' => null,
                ];
            }
        } catch (\Throwable $e) {
            $lastError = $e->getMessage();
        }

        return [
            'source' => null,
            'markets' => [],
            'gamma_status' => $gammaStatus,
            'clob_status' => null,
            'http_error' => $lastError,
        ];
    }

    private function flattenGammaEventsToMarkets(array $events): array
    {
        $flattened = [];
        $seen = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $eventMeta = $event;
            unset($eventMeta['markets']);
            $eventMeta = $this->stripNestedDepth($eventMeta);

            $eventMarkets = $event['markets'] ?? [];
            if (!is_array($eventMarkets)) {
                continue;
            }

            foreach ($eventMarkets as $market) {
                if (!is_array($market)) {
                    continue;
                }

                $marketWithEvent = $market;
                $marketWithEvent['events'] = [$eventMeta];

                // Carry event-level hints into market-level fields if missing.
                $marketWithEvent['groupItemTitle'] = $marketWithEvent['groupItemTitle'] ?? ($event['title'] ?? null);
                $marketWithEvent['seriesSlug'] = $marketWithEvent['seriesSlug'] ?? ($event['seriesSlug'] ?? null);
                $marketWithEvent['ticker'] = $marketWithEvent['ticker'] ?? ($event['ticker'] ?? null);
                $marketWithEvent['category'] = $marketWithEvent['category'] ?? ($event['category'] ?? null);

                $dedupeKey = (string) ($marketWithEvent['conditionId']
                    ?? $marketWithEvent['condition_id']
                    ?? $marketWithEvent['id']
                    ?? $marketWithEvent['slug']
                    ?? spl_object_hash((object) $marketWithEvent));

                if ($dedupeKey !== '' && isset($seen[$dedupeKey])) {
                    continue;
                }
                if ($dedupeKey !== '') {
                    $seen[$dedupeKey] = true;
                }

                $flattened[] = $marketWithEvent;
            }
        }

        return $flattened;
    }

    private function stripNestedDepth(array $event): array
    {
        // Keep event metadata lightweight when embedding back into each market.
        unset($event['markets']);

        if (isset($event['series']) && is_array($event['series'])) {
            $event['series'] = array_map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }

                return [
                    'slug' => $item['slug'] ?? null,
                    'title' => $item['title'] ?? null,
                    'interval' => $item['interval'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'recurrence' => $item['recurrence'] ?? null,
                    'seriesType' => $item['seriesType'] ?? null,
                    'secondsDelay' => $item['secondsDelay'] ?? null,
                    'seconds_delay' => $item['seconds_delay'] ?? null,
                ];
            }, $event['series']);
        }

        return $event;
    }
}
