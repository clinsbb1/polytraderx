<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\Trade;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;

class RiskManager
{
    public function __construct(
        private SettingsService $settings,
        private SubscriptionService $subscriptionService,
    ) {}

    public function canTrade(int $userId): array
    {
        $checks = [];

        if (!$this->isSimulatorEnabled($userId)) {
            $checks['simulator_enabled'] = false;
            return ['allowed' => false, 'reason' => 'Simulator is disabled', 'checks' => $checks];
        }
        $checks['simulator_enabled'] = true;

        if (!$this->checkDailyLossLimit($userId)) {
            $checks['daily_loss'] = false;
            return ['allowed' => false, 'reason' => 'Daily loss limit reached', 'checks' => $checks];
        }
        $checks['daily_loss'] = true;

        if (!$this->checkDailyTradeCount($userId)) {
            $checks['daily_trades'] = false;
            return ['allowed' => false, 'reason' => 'Daily trade limit reached', 'checks' => $checks];
        }
        $checks['daily_trades'] = true;

        if (!$this->checkConcurrentPositions($userId)) {
            $checks['concurrent_positions'] = false;
            return ['allowed' => false, 'reason' => 'Max concurrent positions reached', 'checks' => $checks];
        }
        $checks['concurrent_positions'] = true;

        if (!$this->checkSubscriptionLimits($userId)) {
            $checks['subscription_limits'] = false;
            return ['allowed' => false, 'reason' => 'Subscription plan limit reached', 'checks' => $checks];
        }
        $checks['subscription_limits'] = true;

        return ['allowed' => true, 'reason' => null, 'checks' => $checks];
    }

    public function isSimulatorEnabled(int $userId): bool
    {
        return $this->settings->getBool('SIMULATOR_ENABLED', false, $userId);
    }

    public function checkDailyLossLimit(int $userId): bool
    {
        $maxLoss = $this->settings->getFloat('MAX_DAILY_LOSS', 50.0, $userId);
        $todayLoss = abs($this->getDailyPnL($userId));

        return $todayLoss < $maxLoss;
    }

    public function checkDailyTradeCount(int $userId): bool
    {
        $maxTrades = $this->settings->getInt('MAX_DAILY_TRADES', 48, $userId);
        $todayCount = $this->getDailyTradeCount($userId);

        return $todayCount < $maxTrades;
    }

    public function checkConcurrentPositions(int $userId): bool
    {
        $maxConcurrent = $this->settings->getInt('MAX_CONCURRENT_POSITIONS', 3, $userId);
        $openCount = $this->getOpenPositionCount($userId);

        return $openCount < $maxConcurrent;
    }

    public function checkSubscriptionLimits(int $userId): bool
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        // Use the new SubscriptionService API
        $canSimulate = $this->subscriptionService->canSimulateMore($user);

        // Check concurrent positions against plan limit
        $openCount = $this->getOpenPositionCount($userId);
        $maxConcurrent = $this->subscriptionService->getMaxConcurrentPositions($user);
        $withinConcurrent = $maxConcurrent === 0 || $openCount < $maxConcurrent;

        return $canSimulate && $withinConcurrent;
    }

    public function calculateBetSize(float $confidence, float $currentBankroll, int $userId): float
    {
        $maxBet = max(0.01, $this->settings->getFloat('MAX_BET_AMOUNT', 10.0, $userId));
        $maxPct = max(0.01, $this->settings->getFloat('MAX_BET_PERCENTAGE', 10.0, $userId));
        $effectiveBankroll = max(1.0, $currentBankroll);

        $pctLimit = max(0.01, $effectiveBankroll * ($maxPct / 100.0));
        $base = min($maxBet, $pctLimit);

        // Scale from 0 at confidence=0.90 to full at confidence=1.0
        $scale = max(0.0, min(1.0, ($confidence - 0.90) / 0.10));
        $betSize = $base * $scale;

        // Never return zero and never exceed the computed cap.
        $minimumExecutable = max(0.01, min(1.0, $base));
        $betSize = max($minimumExecutable, min($betSize, $base));

        return round($betSize, 2);
    }

    public function getDailyPnL(int $userId): float
    {
        $losses = (float) Trade::forUser($userId)
            ->lost()
            ->today()
            ->sum('pnl');

        return $losses;
    }

    public function getDailyTradeCount(int $userId): int
    {
        return (int) Trade::forUser($userId)
            ->today()
            ->count();
    }

    public function getOpenPositionCount(int $userId): int
    {
        return (int) Trade::forUser($userId)
            ->open()
            ->count();
    }
}
