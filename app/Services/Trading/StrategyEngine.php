<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\Trade;
use App\Models\TradeLog;
use App\Models\User;
use App\Services\Polymarket\BalanceService;
use App\Services\Polymarket\MarketService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\PriceFeed\BinanceService;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class StrategyEngine
{
    public function __construct(
        private MarketService $marketService,
        private MarketTimingService $timingService,
        private BinanceService $binanceService,
        private PriceAggregator $priceAggregator,
        private SignalGenerator $signalGenerator,
        private TradeExecutor $tradeExecutor,
        private SettingsService $settings,
    ) {}

    public function runForUser(User $user): array
    {
        $summary = [
            'user_id' => $user->id,
            'markets_scanned' => 0,
            'in_entry_window' => 0,
            'signals_generated' => 0,
            'trades_placed' => 0,
            'skipped' => [],
        ];

        if (!$this->settings->getBool('BOT_ENABLED', true, $user->id)) {
            $summary['skipped'][] = 'Bot disabled';
            return $summary;
        }

        try {
            $client = new PolymarketClient($user);
        } catch (\Exception $e) {
            $summary['skipped'][] = 'No Polymarket credentials: ' . $e->getMessage();
            return $summary;
        }

        // Fetch markets
        $markets = $this->marketService->getActiveCryptoMarkets($client);
        $summary['markets_scanned'] = $markets->count();

        // Filter to entry window
        $entryMarkets = $this->timingService->getActiveEntryWindows($markets, $user->id);
        $summary['in_entry_window'] = $entryMarkets->count();

        if ($entryMarkets->isEmpty()) {
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
                    continue;
                }

                // Get spot data
                $polymarketPrices = [
                    'yes_price' => $market['yes_price'],
                    'no_price' => $market['no_price'],
                ];
                $spotData = $this->priceAggregator->getMarketContext($market['asset'], $polymarketPrices);

                // Get Muscles AI analysis (null if unavailable)
                $musclesResult = null;
                try {
                    $aiRouter = app(\App\Services\AI\AIRouter::class);
                    $musclesResult = $aiRouter->getMusclesAnalysis($market, $spotData, $user->id);
                } catch (\Exception $e) {
                    Log::channel('bot')->debug('Muscles unavailable, using reflexes only', [
                        'user_id' => $user->id,
                        'message' => $e->getMessage(),
                    ]);
                }

                // Generate signal
                $signal = $this->signalGenerator->generateSignal($market, $spotData, $user->id, $bankroll, $musclesResult);
                $summary['signals_generated']++;

                if ($signal['action'] !== 'EXECUTE') {
                    $summary['skipped'][] = $signal['reasoning'];
                    continue;
                }

                // Re-check concurrent positions (may have changed from earlier trade in this batch)
                $openCount = Trade::forUser($user->id)->open()->count();
                $maxConcurrent = $this->settings->getInt('MAX_CONCURRENT_POSITIONS', 3, $user->id);
                if ($openCount >= $maxConcurrent) {
                    $summary['skipped'][] = 'Max concurrent positions reached during batch';
                    continue;
                }

                // Execute trade
                $trade = $this->tradeExecutor->execute($signal, $market, $spotData, $user);

                if ($trade !== null) {
                    $summary['trades_placed']++;
                }
            } catch (\Exception $e) {
                Log::channel('bot')->error('Error processing market for user', [
                    'user_id' => $user->id,
                    'market' => $market['condition_id'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ]);
                $summary['skipped'][] = "Error on {$market['asset']}: {$e->getMessage()}";
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
                if (!$trade->market_end_time || now()->lt($trade->market_end_time)) {
                    continue;
                }

                $isDryRun = $this->settings->getBool('DRY_RUN', true, $user->id);

                // Determine outcome
                $outcome = $this->resolveTradeOutcome($trade, $user, $isDryRun);

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
                    $spotAtResolution = $this->binanceService->getPriceForAsset($trade->asset);
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
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'created_at' => now(),
                ]);

                $summary['resolved']++;
                $summary[$status]++;

                Log::channel('bot')->info('Trade resolved', [
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
            } catch (\Exception $e) {
                Log::channel('bot')->error('Error resolving trade', [
                    'user_id' => $user->id,
                    'trade_id' => $trade->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    private function resolveTradeOutcome(Trade $trade, User $user, bool $isDryRun): ?array
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
                if (now()->diffInMinutes($trade->market_end_time) < 5) {
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
            Log::channel('bot')->warning('Failed to fetch market resolution, using price fallback', [
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
            $currentPrice = $this->binanceService->getPriceForAsset($trade->asset);
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
        try {
            $balanceService = new BalanceService($client);
            return $balanceService->getTotalEquity();
        } catch (\Exception $e) {
            // Fallback: use a conservative estimate
            Log::channel('bot')->warning('Could not fetch bankroll, using default', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
            return 100.0;
        }
    }
}
