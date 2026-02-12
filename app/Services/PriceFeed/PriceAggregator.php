<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use Illuminate\Support\Facades\Log;

class PriceAggregator
{
    public function __construct(
        private BinanceService $binance,
        private VolatilityCalculator $volatility,
    ) {}

    public function getMarketContext(string $asset, array $polymarketPrices): array
    {
        $priceChange = $this->binance->getPriceChange($asset, 15);
        $change1m = $this->binance->getPriceChange($asset, 1);
        $change5m = $this->binance->getPriceChange($asset, 5);

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
            'directions_agree' => $directionsAgree,
            'desync_detected' => !$directionsAgree,
            'desync_details' => $desyncDetails,
        ];
    }

    public function detectDesync(string $asset, array $polymarketPrices): ?string
    {
        $context = $this->getMarketContext($asset, $polymarketPrices);

        if ($context['desync_detected']) {
            Log::channel('bot')->warning('Price feed desync detected', [
                'asset' => $asset,
                'details' => $context['desync_details'],
            ]);
            return $context['desync_details'];
        }

        return null;
    }

    public function calculateTrueProbability(string $asset, int $secondsRemaining): float
    {
        $priceChange = $this->binance->getPriceChange($asset, 15);
        $changeMagnitude = abs($priceChange['change_pct']);

        $reversalProb = $this->volatility->estimateReversalProbability(
            $asset,
            $priceChange['change_pct'],
            $secondsRemaining
        );

        // True probability that current direction holds = 1 - reversal probability
        return round(1.0 - $reversalProb, 4);
    }
}
