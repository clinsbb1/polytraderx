<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\BalanceSnapshot;
use App\Models\BotActivityLog;
use App\Models\Trade;
use App\Models\TradeLog;
use App\Models\User;
use App\Services\Polymarket\BalanceService;
use App\Services\Polymarket\MarketService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StrategyEngine
{
    private const POLYMARKET_RESOLUTION_BUFFER_MINUTES = 1;
    private const END_TIME_DRIFT_BUFFER_MINUTES = 2;
    private const DURATION_GRACE_MINUTES = 2;

    public function __construct(
        private MarketService $marketService,
        private MarketTimingService $timingService,
        private PriceAggregator $priceAggregator,
        private SignalGenerator $signalGenerator,
        private TradeExecutor $tradeExecutor,
        private SettingsService $settings,
        private SubscriptionService $subscriptionService,
        private SimulationBalanceService $simulationBalanceService,
    ) {}

    public function runForUser(User $user): array
    {
        $cycleId = (string) Str::uuid();
        $summary = [
            'user_id' => $user->id,
            'cycle_id' => $cycleId,
            'markets_scanned' => 0,
            'in_entry_window' => 0,
            'signals_generated' => 0,
            'trades_placed' => 0,
            'skipped' => [],
        ];

        if (!$this->settings->getBool('SIMULATOR_ENABLED', false, $user->id)) {
            $summary['skipped'][] = 'Simulator disabled';
            $this->logBotActivity($user->id, $cycleId, 'cycle_skipped', 'Simulator is disabled for this user.');
            return $summary;
        }

        // Hard guard: never run simulator cycles when simulated cash balance is depleted.
        try {
            $state = $this->simulationBalanceService->calculateForUser($user->id);
            $balance = (float) ($state['balance'] ?? 0.0);

            if (!is_finite($balance) || $balance <= 0.0) {
                $this->settings->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);
                $summary['skipped'][] = 'Simulator auto-disabled: balance is $0.00 or below';
                $this->logBotActivity(
                    $user->id,
                    $cycleId,
                    'cycle_skipped',
                    'Simulator auto-disabled: simulated balance is $0.00 or below.',
                    context: ['balance' => $balance]
                );

                try {
                    app(\App\Services\Telegram\NotificationService::class)->notifyBotPaused(
                        'Balance is $0.00 or below. Simulator has been turned off. Reset your balance in your account, then re-enable the simulator.',
                        $user
                    );
                } catch (\Throwable) {
                    // Notification failure must never break simulator safety guard.
                }

                return $summary;
            }
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Failed to evaluate simulated balance before cycle', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        // Check signal limit
        if (!$this->subscriptionService->canSimulateMore($user)) {
            Log::channel('simulator')->info("User {$user->account_id} hit daily signal limit");
            $summary['skipped'][] = 'Daily signal limit reached';
            $this->logBotActivity($user->id, $cycleId, 'cycle_skipped', 'Daily signal limit reached.');
            return $summary;
        }

        $client = new PolymarketClient($user);

        // Fetch markets (filtered by user's selected durations)
        $markets = $this->marketService->getActiveCryptoMarkets($client, $user->id);
        $summary['markets_scanned'] = $markets->count();
        $scanned5Min = $markets->filter(fn(array $market) => ($market['duration'] ?? null) === '5min')->count();
        $scanned15Min = $markets->filter(fn(array $market) => ($market['duration'] ?? null) === '15min')->count();
        $assetBreakdown = [
            'BTC' => $markets->filter(fn(array $market) => ($market['asset'] ?? null) === 'BTC')->count(),
            'ETH' => $markets->filter(fn(array $market) => ($market['asset'] ?? null) === 'ETH')->count(),
            'SOL' => $markets->filter(fn(array $market) => ($market['asset'] ?? null) === 'SOL')->count(),
            'XRP' => $markets->filter(fn(array $market) => ($market['asset'] ?? null) === 'XRP')->count(),
        ];
        $nearestCloseSeconds = $markets->isNotEmpty()
            ? (int) $markets->min(fn(array $market) => (int) ($market['seconds_remaining'] ?? PHP_INT_MAX))
            : null;
        $withinFiveMinutes = $markets
            ->filter(fn(array $market) => ((int) ($market['seconds_remaining'] ?? 0)) > 0)
            ->filter(fn(array $market) => ((int) ($market['seconds_remaining'] ?? 0)) <= 300)
            ->count();
        $entryWindow = $this->timingService->getEntryWindowRange($user->id);
        $entryWindowMin = $entryWindow['min'];
        $entryWindowMax = $entryWindow['max'];

        // Filter to entry window
        $entryMarkets = $this->timingService->getActiveEntryWindows($markets, $user->id);
        $summary['in_entry_window'] = $entryMarkets->count();

        $this->logBotActivity(
            $user->id,
            $cycleId,
            'cycle_scanned',
            "Scanned {$summary['markets_scanned']} market(s) [5min: {$scanned5Min}, 15min: {$scanned15Min}] [BTC: {$assetBreakdown['BTC']}, ETH: {$assetBreakdown['ETH']}, SOL: {$assetBreakdown['SOL']}, XRP: {$assetBreakdown['XRP']}], {$summary['in_entry_window']} in entry window (window={$entryWindowMin}-{$entryWindowMax}s, nearest_close=" . ($nearestCloseSeconds ?? 'n/a') . "s).",
            context: [
                'markets_scanned' => $summary['markets_scanned'],
                'markets_scanned_5min' => $scanned5Min,
                'markets_scanned_15min' => $scanned15Min,
                'markets_scanned_by_asset' => $assetBreakdown,
                'in_entry_window' => $summary['in_entry_window'],
                'entry_window_min_seconds' => $entryWindowMin,
                'entry_window_max_seconds' => $entryWindowMax,
                'nearest_close_seconds' => $nearestCloseSeconds,
                'markets_within_5m' => $withinFiveMinutes,
            ]
        );

        if ($entryMarkets->isEmpty()) {
            $sampledMarkets = $markets
                ->sortBy(fn(array $market) => (int) ($market['seconds_remaining'] ?? PHP_INT_MAX))
                ->take(15)
                ->values();

            foreach ($sampledMarkets as $market) {
                $secondsRemaining = (int) ($market['seconds_remaining'] ?? 0);
                $this->logMarketActivity(
                    userId: $user->id,
                    cycleId: $cycleId,
                    market: $market,
                    matched: false,
                    action: 'SKIP_NOT_IN_ENTRY_WINDOW',
                    message: "Scanned market outside entry window ({$secondsRemaining}s remaining).",
                    context: [
                        'entry_window_min_seconds' => $entryWindowMin,
                        'entry_window_max_seconds' => $entryWindowMax,
                        'seconds_remaining' => $secondsRemaining,
                        'duration' => $market['duration'] ?? null,
                    ]
                );
            }

            $this->logBotActivity($user->id, $cycleId, 'no_match', 'No market in entry window matched current strategy.');
            return $summary;
        }

        // Get bankroll for bet sizing
        $bankroll = $this->estimateBankroll($client, $user);

        foreach ($entryMarkets as $market) {
            try {
                // Idempotency: skip if we already have a position on this market
                $existingTrade = Trade::forUser($user->id)
                    ->where('market_id', $market['condition_id'])
                    ->whereIn('status', ['pending', 'open'])
                    ->exists();

                if ($existingTrade) {
                    $summary['skipped'][] = "Already have position on {$market['asset']} market {$market['condition_id']}";
                    $this->logMarketActivity(
                        userId: $user->id,
                        cycleId: $cycleId,
                        market: $market,
                        matched: false,
                        action: 'SKIP_EXISTING_POSITION',
                        message: 'Skipped: already have an open/pending position on this market.',
                        context: ['reason' => 'existing_position']
                    );
                    continue;
                }

                // Get spot data
                $polymarketPrices = [
                    'yes_price' => $market['yes_price'],
                    'no_price' => $market['no_price'],
                ];
                $spotData = $this->priceAggregator->getMarketContext($market['asset'], $polymarketPrices, $user->id);

                // Generate baseline signal with reflexes first. This avoids paid AI calls on obvious SKIPs.
                $baselineSignal = $this->signalGenerator->generateSignal($market, $spotData, $user->id, $bankroll, null);
                $summary['signals_generated']++;

                if ($baselineSignal['action'] !== 'EXECUTE') {
                    $summary['skipped'][] = $baselineSignal['reasoning'];
                    $this->logMarketActivity(
                        userId: $user->id,
                        cycleId: $cycleId,
                        market: $market,
                        matched: false,
                        action: (string) ($baselineSignal['action'] ?? 'SKIP'),
                        message: (string) ($baselineSignal['reasoning'] ?? 'Market did not match strategy'),
                        context: [
                            'signal' => $baselineSignal,
                            'spot' => $spotData,
                        ]
                    );
                    continue;
                }

                // Baseline says execute: now optionally refine/override with Muscles.
                $musclesResult = null;
                $scanMode = $this->settings->get('SCAN_MODE', 'reflexes', $user->id);
                if ($scanMode === 'muscles') {
                    try {
                        $aiRouter = app(\App\Services\AI\AIRouter::class);
                        $musclesResult = $aiRouter->getMusclesAnalysis($market, $spotData, $user->id);
                    } catch (\Exception $e) {
                        Log::channel('simulator')->debug('Muscles unavailable, using reflexes only', [
                            'user_id' => $user->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                $signal = $baselineSignal;
                if (is_array($musclesResult) && isset($musclesResult['confidence'])) {
                    $signal = $this->signalGenerator->generateSignal($market, $spotData, $user->id, $bankroll, $musclesResult);
                } elseif (is_array($musclesResult) && isset($musclesResult['message'])) {
                    $signal['muscles_result'] = $musclesResult;
                }

                if ($signal['action'] !== 'EXECUTE') {
                    $summary['skipped'][] = $signal['reasoning'];
                    $this->logMarketActivity(
                        userId: $user->id,
                        cycleId: $cycleId,
                        market: $market,
                        matched: false,
                        action: (string) ($signal['action'] ?? 'SKIP'),
                        message: (string) ($signal['reasoning'] ?? 'Market did not match strategy'),
                        context: [
                            'signal' => $signal,
                            'spot' => $spotData,
                        ]
                    );
                    continue;
                }

                // Re-check concurrent positions (may have changed from earlier trade in this batch)
                $openCount = Trade::forUser($user->id)->open()->count();
                $maxConcurrent = $this->settings->getInt('MAX_CONCURRENT_POSITIONS', 3, $user->id);
                if ($openCount >= $maxConcurrent) {
                    $summary['skipped'][] = 'Max concurrent positions reached during batch';
                    $this->logMarketActivity(
                        userId: $user->id,
                        cycleId: $cycleId,
                        market: $market,
                        matched: true,
                        action: 'SKIP_MAX_CONCURRENT',
                        message: 'Matched strategy but skipped: max concurrent positions reached.',
                        context: [
                            'open_positions' => $openCount,
                            'max_concurrent' => $maxConcurrent,
                        ]
                    );
                    continue;
                }

                // Execute trade
                $trade = $this->tradeExecutor->execute($signal, $market, $spotData, $user);

                if ($trade !== null) {
                    $summary['trades_placed']++;
                    $bankroll = max(0.0, $bankroll - (float) $trade->amount);
                    $this->logMarketActivity(
                        userId: $user->id,
                        cycleId: $cycleId,
                        market: $market,
                        matched: true,
                        action: 'TRADE_PLACED',
                        message: 'Matched strategy and trade was placed.',
                        context: [
                            'trade_id' => $trade->id,
                            'side' => $trade->side,
                            'amount' => $trade->amount,
                        ]
                    );
                } else {
                    $this->logMarketActivity(
                        userId: $user->id,
                        cycleId: $cycleId,
                        market: $market,
                        matched: true,
                        action: 'MATCHED_NO_TRADE',
                        message: 'Matched strategy but no trade was placed.',
                        context: [
                            'signal' => $signal,
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::channel('simulator')->error('Error processing market for user', [
                    'user_id' => $user->id,
                    'market' => $market['condition_id'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ]);
                $summary['skipped'][] = "Error on {$market['asset']}: {$e->getMessage()}";
                $this->logMarketActivity(
                    userId: $user->id,
                    cycleId: $cycleId,
                    market: $market,
                    matched: false,
                    action: 'ERROR',
                    message: "Error while evaluating market: {$e->getMessage()}",
                    context: ['exception' => $e->getMessage()]
                );
            }
        }

        return $summary;
    }

    public function checkResolutions(User $user): array
    {
        $summary = [
            'user_id' => $user->id,
            'checked' => 0,
            'resolved' => 0,
            'won' => 0,
            'lost' => 0,
        ];

        $openTrades = Trade::forUser($user->id)
            ->open()
            ->get();

        $summary['checked'] = $openTrades->count();

        foreach ($openTrades as $trade) {
            try {
                $resolutionGate = $this->evaluateResolutionGate($trade);
                if (!$resolutionGate['ready']) {
                    continue;
                }

                $isDryRun = $this->settings->getBool('DRY_RUN', true, $user->id);

                // Determine outcome
                $outcome = $this->resolveTradeOutcome(
                    $trade,
                    $user,
                    $isDryRun,
                    $resolutionGate['effective_end_time']
                );

                if ($outcome === null) {
                    continue;
                }

                $status = $outcome['won'] ? 'won' : 'lost';
                $pnl = $outcome['won']
                    ? round($trade->potential_payout - (float) $trade->amount, 2)
                    : -1 * (float) $trade->amount;

                // Fetch spot price at resolution
                $spotAtResolution = null;
                try {
                    $spotAtResolution = $this->priceAggregator->getSpotPriceForUser($trade->asset, $user->id);
                } catch (\Exception $e) {
                    // Non-critical
                }

                $trade->update([
                    'status' => $status,
                    'exit_price' => $outcome['exit_price'] ?? ($status === 'won' ? 1.0 : 0.0),
                    'resolved_at' => now(),
                    'pnl' => $pnl,
                    'external_spot_at_resolution' => $spotAtResolution,
                    'audited' => false,
                ]);

                TradeLog::create([
                    'user_id' => $user->id,
                    'trade_id' => $trade->id,
                    'event' => 'resolved',
                    'data' => [
                        'status' => $status,
                        'pnl' => $pnl,
                        'exit_price' => $outcome['exit_price'] ?? null,
                        'spot_at_resolution' => $spotAtResolution,
                        'dry_run' => $isDryRun,
                        'resolution_method' => $outcome['method'],
                        'resolution_trigger' => $resolutionGate['trigger'],
                        'effective_market_end_time' => $resolutionGate['effective_end_time']?->toIso8601String(),
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'created_at' => now(),
                ]);

                $summary['resolved']++;
                $summary[$status]++;

                Log::channel('simulator')->info('Trade resolved', [
                    'user_id' => $user->id,
                    'trade_id' => $trade->id,
                    'asset' => $trade->asset,
                    'side' => $trade->side,
                    'status' => $status,
                    'pnl' => $pnl,
                ]);

                try {
                    app(\App\Services\Telegram\NotificationService::class)->notifyTradeResolved($trade);
                } catch (\Exception $e) {
                    // Notification failure must never crash trading
                }

                try {
                    app(\App\Services\Trading\SimulationBalanceService::class)->snapshotForUser($user->id);
                } catch (\Throwable) {
                    // Snapshot failure must never crash trade resolution.
                }
            } catch (\Exception $e) {
                Log::channel('simulator')->error('Error resolving trade', [
                    'user_id' => $user->id,
                    'trade_id' => $trade->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    private function resolveTradeOutcome(
        Trade $trade,
        User $user,
        bool $isDryRun,
        ?Carbon $expectedEndTime = null
    ): ?array
    {
        if ($isDryRun) {
            return $this->resolveDryRunTrade($trade);
        }

        // Live: try to fetch resolution from Polymarket
        try {
            $client = new PolymarketClient($user);
            $marketData = $this->marketService->getMarketDetails($client, $trade->market_id);

            $resolved = $marketData['resolved'] ?? $marketData['is_resolved'] ?? false;
            if (!$resolved) {
                // Market may not have resolved yet — give it a buffer
                $referenceEndTime = $expectedEndTime ?? $this->resolveEffectiveEndTime($trade);
                $minutesSinceExpectedEnd = $referenceEndTime
                    ? now()->diffInMinutes($referenceEndTime, false)
                    : self::POLYMARKET_RESOLUTION_BUFFER_MINUTES;

                if ($minutesSinceExpectedEnd < self::POLYMARKET_RESOLUTION_BUFFER_MINUTES) {
                    return null;
                }
            }

            $resolution = strtoupper($marketData['resolution'] ?? $marketData['outcome'] ?? '');

            if (empty($resolution)) {
                // Fallback: use Binance price to infer
                return $this->resolveDryRunTrade($trade);
            }

            $won = ($trade->side === $resolution);

            return [
                'won' => $won,
                'exit_price' => $won ? 1.0 : 0.0,
                'method' => 'polymarket_resolution',
            ];
        } catch (\Exception $e) {
            Log::channel('simulator')->warning('Failed to fetch market resolution, using price fallback', [
                'trade_id' => $trade->id,
                'message' => $e->getMessage(),
            ]);
            return $this->resolveDryRunTrade($trade);
        }
    }

    private function resolveDryRunTrade(Trade $trade): array
    {
        // Use Binance to infer: did the price go up or down over the market period?
        try {
            $currentPrice = $this->priceAggregator->getSpotPriceForUser($trade->asset, $trade->user_id);
            $entrySpot = (float) $trade->external_spot_at_entry;

            if ($entrySpot <= 0) {
                // Can't determine — assume loss for safety
                return ['won' => false, 'exit_price' => 0.0, 'method' => 'unknown_default_loss'];
            }

            $priceWentUp = $currentPrice > $entrySpot;
            $marketResolvedYes = $priceWentUp;

            $won = ($trade->side === 'YES' && $marketResolvedYes)
                || ($trade->side === 'NO' && !$marketResolvedYes);

            return [
                'won' => $won,
                'exit_price' => $won ? 1.0 : 0.0,
                'method' => 'binance_price_comparison',
            ];
        } catch (\Exception $e) {
            return ['won' => false, 'exit_price' => 0.0, 'method' => 'error_default_loss'];
        }
    }

    private function estimateBankroll(PolymarketClient $client, User $user): float
    {
        // Simulation-first: prefer latest simulated equity snapshot.
        $latestSnapshot = BalanceSnapshot::forUser($user->id)
            ->latest('snapshot_at')
            ->first();

        if ($latestSnapshot && (float) $latestSnapshot->total_equity > 0) {
            return round((float) $latestSnapshot->total_equity, 2);
        }

        try {
            $balanceService = new BalanceService($client);
            $equity = (float) $balanceService->getTotalEquity();
            if ($equity > 0) {
                return round($equity, 2);
            }
        } catch (\Exception $e) {
            Log::channel('simulator')->warning('Could not fetch bankroll, using default', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        // Safe simulation fallback to avoid zero-sized trades.
        return 100.0;
    }

    private function evaluateResolutionGate(Trade $trade): array
    {
        $effectiveEndTime = $this->resolveEffectiveEndTime($trade);
        $now = now();

        if ($effectiveEndTime !== null && $now->greaterThanOrEqualTo($effectiveEndTime)) {
            return [
                'ready' => true,
                'trigger' => 'market_end_time',
                'effective_end_time' => $effectiveEndTime,
            ];
        }

        if ($this->isTradeStale($trade, $now)) {
            return [
                'ready' => true,
                'trigger' => 'stale_open_fallback',
                'effective_end_time' => $effectiveEndTime,
            ];
        }

        return [
            'ready' => false,
            'trigger' => 'waiting_for_market_end',
            'effective_end_time' => $effectiveEndTime,
        ];
    }

    private function resolveEffectiveEndTime(Trade $trade): ?Carbon
    {
        $storedEndTime = $trade->market_end_time instanceof \DateTimeInterface
            ? Carbon::instance($trade->market_end_time)
            : null;
        $fallbackEndTime = $this->inferFallbackEndTime($trade);

        if ($storedEndTime === null) {
            return $fallbackEndTime;
        }

        if ($fallbackEndTime === null) {
            return $storedEndTime;
        }

        // If stored end-time drifts too far from the inferred 5m/15m schedule, trust inferred.
        $maxExpected = $fallbackEndTime->copy()->addMinutes(self::END_TIME_DRIFT_BUFFER_MINUTES);

        return $storedEndTime->greaterThan($maxExpected)
            ? $fallbackEndTime
            : $storedEndTime;
    }

    private function inferFallbackEndTime(Trade $trade): ?Carbon
    {
        $entryTime = $trade->entry_at ?? $trade->created_at;

        if (!$entryTime instanceof \DateTimeInterface) {
            return null;
        }

        $durationMinutes = $this->inferDurationMinutesFromTrade($trade);

        return Carbon::instance($entryTime)->addMinutes($durationMinutes);
    }

    private function inferDurationMinutesFromTrade(Trade $trade): int
    {
        $marketSlug = strtoupper((string) $trade->market_slug);
        $marketQuestion = strtoupper((string) $trade->market_question);

        $is15MinuteMarket = preg_match('/(?:\b15\s*[- ]?M(?:IN(?:UTE)?S?)?\b|\bFIFTEEN\s+MIN(?:UTE)?S?\b)/i', $marketSlug) === 1
            || preg_match('/(?:\b15\s*[- ]?M(?:IN(?:UTE)?S?)?\b|\bFIFTEEN\s+MIN(?:UTE)?S?\b)/i', $marketQuestion) === 1;

        if ($is15MinuteMarket) {
            return 15;
        }

        $is5MinuteMarket = preg_match('/(?:\b5\s*[- ]?M(?:IN(?:UTE)?S?)?\b|\bFIVE\s+MIN(?:UTE)?S?\b)/i', $marketSlug) === 1
            || preg_match('/(?:\b5\s*[- ]?M(?:IN(?:UTE)?S?)?\b|\bFIVE\s+MIN(?:UTE)?S?\b)/i', $marketQuestion) === 1;

        if ($is5MinuteMarket) {
            return 5;
        }

        return 15;
    }

    private function isTradeStale(Trade $trade, Carbon $now): bool
    {
        $entryTime = $trade->entry_at ?? $trade->created_at;

        if (!$entryTime instanceof \DateTimeInterface) {
            return false;
        }

        $entryAt = Carbon::instance($entryTime);
        $maxOpenAge = $this->inferDurationMinutesFromTrade($trade) + self::DURATION_GRACE_MINUTES;

        return $now->greaterThanOrEqualTo(
            $entryAt->addMinutes($maxOpenAge)
        );
    }

    private function logBotActivity(
        int $userId,
        string $cycleId,
        string $event,
        string $message,
        ?array $context = null
    ): void {
        try {
            BotActivityLog::create([
                'user_id' => $userId,
                'cycle_id' => $cycleId,
                'event' => $event,
                'market_id' => null,
                'asset' => null,
                'matched_strategy' => null,
                'action' => null,
                'message' => $message,
                'context' => $context,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never break simulator execution due to activity logging.
        }
    }

    private function logMarketActivity(
        int $userId,
        string $cycleId,
        array $market,
        bool $matched,
        string $action,
        string $message,
        ?array $context = null
    ): void {
        try {
            BotActivityLog::create([
                'user_id' => $userId,
                'cycle_id' => $cycleId,
                'event' => 'market_checked',
                'market_id' => (string) ($market['condition_id'] ?? ''),
                'asset' => (string) ($market['asset'] ?? ''),
                'matched_strategy' => $matched,
                'action' => $action,
                'message' => $message,
                'context' => $context,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never break simulator execution due to activity logging.
        }
    }
}
