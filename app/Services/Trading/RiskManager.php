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

        if (!$this->isBotEnabled($userId)) {
            $checks['bot_enabled'] = false;
            return ['allowed' => false, 'reason' => 'Bot is disabled', 'checks' => $checks];
        }
        $checks['bot_enabled'] = true;

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

    public function isBotEnabled(int $userId): bool
    {
        return $this->settings->getBool('BOT_ENABLED', true, $userId);
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
        $dailyCount = $this->getDailyTradeCount($userId);
        $openCount = $this->getOpenPositionCount($userId);

        $withinDaily = $this->subscriptionService->isWithinLimits($userId, 'max_daily_trades', $dailyCount);
        $withinConcurrent = $this->subscriptionService->isWithinLimits($userId, 'max_concurrent_positions', $openCount);

        return $withinDaily && $withinConcurrent;
    }

    public function calculateBetSize(float $confidence, float $currentBankroll, int $userId): float
    {
        $maxBet = $this->settings->getFloat('MAX_BET_AMOUNT', 10.0, $userId);
        $maxPct = $this->settings->getFloat('MAX_BET_PERCENTAGE', 10.0, $userId);

        $pctLimit = $currentBankroll * ($maxPct / 100.0);
        $base = min($maxBet, $pctLimit);

        // Scale from 0 at confidence=0.90 to full at confidence=1.0
        $scale = max(0.0, min(1.0, ($confidence - 0.90) / 0.10));
        $betSize = $base * $scale;

        // Floor at $1, cap at max
        $betSize = max(1.0, min($betSize, $maxBet));

        // Never exceed percentage of bankroll
        $betSize = min($betSize, $pctLimit);

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
