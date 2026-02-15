<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\PriceFeed\PriceAggregator;
use App\Services\PriceFeed\VolatilityCalculator;
use App\Services\Settings\SettingsService;
use App\Services\Trading\MarketTimingService;

class ReflexesService
{
    public function __construct(
        private SettingsService $settings,
        private PriceAggregator $priceAggregator,
        private VolatilityCalculator $volatilityCalculator,
        private MarketTimingService $timingService,
    ) {}

    public function evaluate(array $market, array $spotData, int $userId): array
    {
        $rulesPassed = [];
        $rulesFailed = [];
        $details = [];

        // 1. Simulator enabled?
        $simulatorEnabled = $this->settings->getBool('SIMULATOR_ENABLED', false, $userId);
        $details['simulator_enabled'] = $simulatorEnabled;
        if (!$simulatorEnabled) {
            $rulesFailed[] = 'simulator_disabled';
            return $this->skipResult($rulesPassed, $rulesFailed, 'Simulator is disabled for this user', $details);
        }
        $rulesPassed[] = 'simulator_enabled';

        // 2. Entry window?
        $inWindow = $this->timingService->isInEntryWindow($market, $userId);
        $details['in_entry_window'] = $inWindow;
        $details['seconds_remaining'] = $market['seconds_remaining'] ?? 0;
        $details['entry_window_seconds'] = $this->settings->getInt('ENTRY_WINDOW_SECONDS', 60, $userId);
        if (!$inWindow) {
            $rulesFailed[] = 'outside_entry_window';
            return $this->skipResult($rulesPassed, $rulesFailed, 'Market not in entry window', $details);
        }
        $rulesPassed[] = 'entry_window';

        // 3. Asset monitored?
        $monitoredAssets = explode(',', $this->settings->get('MONITORED_ASSETS', 'BTC,ETH,SOL', $userId));
        $monitoredAssets = array_map('trim', $monitoredAssets);
        $asset = $market['asset'] ?? '';
        $details['asset'] = $asset;
        $details['monitored_assets'] = $monitoredAssets;
        if (!in_array($asset, $monitoredAssets)) {
            $rulesFailed[] = 'asset_not_monitored';
            return $this->skipResult($rulesPassed, $rulesFailed, "Asset {$asset} not in monitored list", $details);
        }
        $rulesPassed[] = 'asset_monitored';

        // 4. Desync check
        $polymarketPrices = [
            'yes_price' => $market['yes_price'] ?? 0,
            'no_price' => $market['no_price'] ?? 0,
        ];
        $desync = $spotData['desync_details'] ?? $this->priceAggregator->detectDesync($asset, $polymarketPrices);
        $details['desync'] = $desync;
        if ($desync !== null) {
            $rulesFailed[] = 'desync_detected';
            return $this->skipResult($rulesPassed, $rulesFailed, "Price feed desync: {$desync}", $details);
        }
        $rulesPassed[] = 'no_desync';

        // 5. Price threshold — determine direction and check
        $minEntry = $this->settings->getFloat('MIN_ENTRY_PRICE_THRESHOLD', 0.92, $userId);
        $maxEntry = $this->settings->getFloat('MAX_ENTRY_PRICE_THRESHOLD', 0.08, $userId);
        $yesPrice = (float) ($market['yes_price'] ?? 0);
        $noPrice = (float) ($market['no_price'] ?? 0);
        $changePct = (float) ($spotData['change_since_open_pct'] ?? 0);

        $details['yes_price'] = $yesPrice;
        $details['no_price'] = $noPrice;
        $details['change_pct'] = $changePct;
        $details['min_entry_threshold'] = $minEntry;
        $details['max_entry_threshold'] = $maxEntry;

        $side = null;
        if ($changePct > 0 && $yesPrice >= $minEntry) {
            $side = 'YES';
        } elseif ($changePct < 0 && $noPrice >= $minEntry) {
            $side = 'NO';
        } elseif ($changePct > 0 && $noPrice <= $maxEntry) {
            $side = 'YES';
        } elseif ($changePct < 0 && $yesPrice <= $maxEntry) {
            $side = 'NO';
        }

        $details['determined_side'] = $side;
        if ($side === null) {
            $rulesFailed[] = 'price_threshold_not_met';
            return $this->skipResult($rulesPassed, $rulesFailed, 'No side meets entry price threshold', $details);
        }
        $rulesPassed[] = 'price_threshold';

        // 6. Volatility check
        $isExtreme = $this->volatilityCalculator->isVolatilityExtreme($asset);
        $details['volatility_extreme'] = $isExtreme;
        if ($isExtreme) {
            $rulesFailed[] = 'extreme_volatility';
            return $this->skipResult($rulesPassed, $rulesFailed, 'Extreme volatility detected — too risky', $details);
        }
        $rulesPassed[] = 'volatility_normal';

        // 7. Reversal probability
        $secondsRemaining = (int) ($market['seconds_remaining'] ?? 0);
        $reversalProb = $this->volatilityCalculator->estimateReversalProbability($asset, $changePct, $secondsRemaining);
        $details['reversal_probability'] = $reversalProb;
        if ($reversalProb >= 0.08) {
            $rulesFailed[] = 'high_reversal_risk';
            return $this->skipResult($rulesPassed, $rulesFailed, "Reversal probability too high: " . round($reversalProb * 100, 1) . "%", $details);
        }
        $rulesPassed[] = 'low_reversal_risk';

        // All passed
        $action = $side === 'YES' ? 'BUY_YES' : 'BUY_NO';

        return [
            'action' => $action,
            'side' => $side,
            'rules_passed' => $rulesPassed,
            'rules_failed' => $rulesFailed,
            'reason' => "All rules passed — {$action} on {$asset}",
            'details' => $details,
        ];
    }

    private function skipResult(array $passed, array $failed, string $reason, array $details): array
    {
        return [
            'action' => 'SKIP',
            'side' => null,
            'rules_passed' => $passed,
            'rules_failed' => $failed,
            'reason' => $reason,
            'details' => $details,
        ];
    }
}
