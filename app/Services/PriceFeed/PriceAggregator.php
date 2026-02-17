<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class PriceAggregator
{
    public function __construct(
        private BinanceService $binance,
        private CoinbaseService $coinbase,
        private KrakenService $kraken,
        private CoinGeckoService $coingecko,
        private VolatilityCalculator $volatility,
        private SettingsService $settings,
    ) {}

    public function getMarketContext(string $asset, array $polymarketPrices, ?int $userId = null): array
    {
        $priceChange = $this->getPriceChangeForUser($asset, 15, $userId);
        $change1m = $this->getPriceChangeForUser($asset, 1, $userId);
        $change5m = $this->getPriceChangeForUser($asset, 5, $userId);

        $yesPrice = (float) ($polymarketPrices['yes_price'] ?? 0);
        $noPrice = (float) ($polymarketPrices['no_price'] ?? 0);

        $polymarketImplied = $yesPrice > 0.5 ? 'UP' : 'DOWN';
        $binanceActual = $priceChange['change_pct'] >= 0 ? 'UP' : 'DOWN';
        $directionsAgree = $polymarketImplied === $binanceActual;

        $desyncDetails = null;
        if (!$directionsAgree) {
            $desyncDetails = "Binance says {$binanceActual} ({$priceChange['change_pct']}%) but Polymarket implies {$polymarketImplied} (YES={$yesPrice})";
        }

        return [
            'asset' => $asset,
            'spot_price' => $priceChange['current'],
            'price_at_open' => $priceChange['open'],
            'change_since_open_pct' => $priceChange['change_pct'],
            'change_1m_pct' => $change1m['change_pct'],
            'change_5m_pct' => $change5m['change_pct'],
            'polymarket_yes_price' => $yesPrice,
            'polymarket_no_price' => $noPrice,
            'polymarket_implied_direction' => $polymarketImplied,
            'binance_actual_direction' => $binanceActual,
            'price_feed_source' => $this->resolvePriceFeedSource($userId),
            'directions_agree' => $directionsAgree,
            'desync_detected' => !$directionsAgree,
            'desync_details' => $desyncDetails,
        ];
    }

    public function detectDesync(string $asset, array $polymarketPrices, ?int $userId = null): ?string
    {
        $context = $this->getMarketContext($asset, $polymarketPrices, $userId);

        if ($context['desync_detected']) {
            Log::channel('simulator')->warning('Price feed desync detected', [
                'asset' => $asset,
                'details' => $context['desync_details'],
            ]);
            return $context['desync_details'];
        }

        return null;
    }

    public function calculateTrueProbability(string $asset, int $secondsRemaining, ?int $userId = null): float
    {
        $priceChange = $this->getPriceChangeForUser($asset, 15, $userId);
        $changeMagnitude = abs($priceChange['change_pct']);

        $reversalProb = $this->volatility->estimateReversalProbability(
            $asset,
            $priceChange['change_pct'],
            $secondsRemaining
        );

        // True probability that current direction holds = 1 - reversal probability
        return round(1.0 - $reversalProb, 4);
    }

    public function getSpotPriceForUser(string $asset, ?int $userId = null): float
    {
        return $this->getPriceForUser($asset, $userId);
    }

    private function getPriceChangeForUser(string $asset, int $minutes, ?int $userId): array
    {
        $source = $this->resolvePriceFeedSource($userId);

        try {
            return match ($source) {
                'coinbase' => $this->coinbase->getPriceChange($asset, $minutes),
                'kraken' => $this->kraken->getPriceChange($asset, $minutes),
                'coingecko' => $this->coingecko->getPriceChange($asset, $minutes),
                default => $this->binance->getPriceChange($asset, $minutes),
            };
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Price source failed, falling back to binance', [
                'user_id' => $userId,
                'asset' => $asset,
                'minutes' => $minutes,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return $this->binance->getPriceChange($asset, $minutes);
        }
    }

    private function getPriceForUser(string $asset, ?int $userId): float
    {
        $source = $this->resolvePriceFeedSource($userId);

        try {
            return match ($source) {
                'coinbase' => $this->coinbase->getPriceForAsset($asset),
                'kraken' => $this->kraken->getPriceForAsset($asset),
                'coingecko' => $this->coingecko->getPriceForAsset($asset),
                default => $this->binance->getPriceForAsset($asset),
            };
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Price source failed, falling back to binance', [
                'user_id' => $userId,
                'asset' => $asset,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return $this->binance->getPriceForAsset($asset);
        }
    }

    private function resolvePriceFeedSource(?int $userId): string
    {
        $source = strtolower(trim($this->settings->getString('PRICE_FEED_SOURCE', 'binance', $userId)));

        if ($source === '') {
            return 'binance';
        }

        // Keep accepted labels extensible while defaulting safely.
        return in_array($source, ['binance', 'coingecko', 'coinbase', 'kraken'], true)
            ? $source
            : 'binance';
    }
}
