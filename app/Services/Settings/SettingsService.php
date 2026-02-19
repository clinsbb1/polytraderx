<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\StrategyParam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_PREFIX = 'settings:';
    private const CACHE_TTL = 3600;
    private const DEFAULTS_SCHEMA_VERSION = '2026-02-19';

    public function get(string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $userId = $userId ?? auth()->id();

        return Cache::remember(
            self::CACHE_PREFIX . $userId . ':' . $key,
            self::CACHE_TTL,
            function () use ($key, $default, $userId) {
                $param = StrategyParam::where('user_id', $userId)
                    ->where('key', $key)
                    ->first();

                if (!$param) {
                    return $default;
                }

                return $this->castValue($param->value, $param->type);
            }
        );
    }

    public function set(string $key, mixed $value, string $updatedBy = 'system', ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        $param = StrategyParam::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        if (!$param) {
            return;
        }

        $param->update([
            'previous_value' => $param->value,
            'value' => (string) $value,
            'updated_by' => $updatedBy,
        ]);

        Cache::forget(self::CACHE_PREFIX . $userId . ':' . $key);
        Cache::forget($this->groupCacheKey($userId, $param->group));
    }

    public function getGroup(string $group, ?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        return Cache::remember(
            $this->groupCacheKey($userId, $group),
            self::CACHE_TTL,
            function () use ($group, $userId) {
                // Get user's existing params for this group
                $userParams = StrategyParam::where('user_id', $userId)
                    ->where('group', $group)
                    ->get();

                // Get default params for this group
                $defaults = collect($this->getDefaultParams())
                    ->where('group', $group);

                // Check if user is missing any params from defaults
                $missingKeys = $defaults->pluck('key')
                    ->diff($userParams->pluck('key'));

                // Seed missing params
                if ($missingKeys->isNotEmpty()) {
                    foreach ($defaults as $param) {
                        if ($missingKeys->contains($param['key'])) {
                            StrategyParam::create([
                                'user_id' => $userId,
                                'key' => $param['key'],
                                'value' => (string) $param['value'],
                                'type' => $param['type'],
                                'description' => $param['description'],
                                'group' => $param['group'],
                                'updated_by' => 'system',
                            ]);
                        }
                    }

                    // Re-fetch params after seeding
                    $userParams = StrategyParam::where('user_id', $userId)
                        ->where('group', $group)
                        ->get();
                }

                // Sort by the order defined in defaults
                $defaultKeys = collect($this->getDefaultParams())
                    ->where('group', $group)
                    ->pluck('key')
                    ->values();

                return $userParams->sortBy(function ($param) use ($defaultKeys) {
                    $index = $defaultKeys->search($param->key);
                    return $index !== false ? $index : 999;
                })->values();
            }
        );
    }

    public function getBool(string $key, bool $default = false, ?int $userId = null): bool
    {
        $value = $this->get($key, $default, $userId);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['true', '1', 'yes'], true);
    }

    public function getFloat(string $key, float $default = 0.0, ?int $userId = null): float
    {
        return (float) $this->get($key, $default, $userId);
    }

    public function getInt(string $key, int $default = 0, ?int $userId = null): int
    {
        return (int) $this->get($key, $default, $userId);
    }

    public function getString(string $key, string $default = '', ?int $userId = null): string
    {
        return (string) $this->get($key, $default, $userId);
    }

    public function seedUserParams(int $userId): void
    {
        $defaults = $this->getDefaultParams();

        foreach ($defaults as $param) {
            StrategyParam::updateOrCreate(
                ['user_id' => $userId, 'key' => $param['key']],
                array_merge($param, [
                    'user_id' => $userId,
                    'value' => (string) $param['value'],
                    'updated_by' => 'system',
                ])
            );
        }
    }

    private function getDefaultParams(): array
    {
        return [
            ['key' => 'MAX_BET_AMOUNT', 'value' => '10', 'type' => 'decimal', 'description' => 'Maximum single bet in USDC', 'group' => 'risk'],
            ['key' => 'MAX_BET_PERCENTAGE', 'value' => '10.0', 'type' => 'decimal', 'description' => 'Max bet as % of current bankroll', 'group' => 'risk'],
            ['key' => 'MAX_DAILY_LOSS', 'value' => '50', 'type' => 'decimal', 'description' => 'Stop all trading after this daily loss in USDC', 'group' => 'risk'],
            ['key' => 'MAX_DAILY_TRADES', 'value' => '48', 'type' => 'number', 'description' => 'Max trades per day', 'group' => 'risk'],
            ['key' => 'MAX_CONCURRENT_POSITIONS', 'value' => '3', 'type' => 'number', 'description' => 'Max open bets at once', 'group' => 'risk'],
            ['key' => 'SIMULATOR_ENABLED', 'value' => 'false', 'type' => 'boolean', 'description' => 'Simulator Enabled - Master on/off switch', 'group' => 'trading'],
            ['key' => 'MIN_CONFIDENCE_SCORE', 'value' => '0.92', 'type' => 'decimal', 'description' => 'Minimum AI confidence to trade', 'group' => 'trading'],
            ['key' => 'MIN_ENTRY_PRICE_THRESHOLD', 'value' => '0.92', 'type' => 'decimal', 'description' => 'Only buy locked-in side at this price or above', 'group' => 'trading'],
            ['key' => 'MAX_ENTRY_PRICE_THRESHOLD', 'value' => '0.08', 'type' => 'decimal', 'description' => 'Only buy cheap contrarian side at this price or below', 'group' => 'trading'],
            ['key' => 'ENTRY_WINDOW_MIN_SECONDS', 'value' => '5', 'type' => 'number', 'description' => 'Earliest seconds-before-close allowed for entry', 'group' => 'trading'],
            ['key' => 'ENTRY_WINDOW_MAX_SECONDS', 'value' => '60', 'type' => 'number', 'description' => 'Latest seconds-before-close allowed for entry', 'group' => 'trading'],
            ['key' => 'ENTRY_WINDOW_SECONDS', 'value' => '60', 'type' => 'number', 'description' => '[Legacy fallback] single entry window max seconds', 'group' => 'trading'],
            ['key' => 'DRY_RUN', 'value' => 'true', 'type' => 'boolean', 'description' => 'Paper trading mode', 'group' => 'trading'],
            ['key' => 'PRICE_FEED_SOURCE', 'value' => 'binance', 'type' => 'string', 'description' => 'Price source used for simulation context (default: binance)', 'group' => 'trading'],
            ['key' => 'MONITORED_ASSETS', 'value' => 'BTC,ETH,SOL,XRP', 'type' => 'string', 'description' => 'Comma-separated list of monitored assets', 'group' => 'trading'],
            ['key' => 'MARKET_DURATIONS', 'value' => '5min,15min', 'type' => 'string', 'description' => 'Which market durations to trade (5min, 15min, or both)', 'group' => 'trading'],
            ['key' => 'AI_AUTO_APPLY_FIXES', 'value' => 'false', 'type' => 'boolean', 'description' => 'Auto-apply low-risk AI suggestions', 'group' => 'ai'],
            ['key' => 'NOTIFY_DAILY_PNL', 'value' => 'true', 'type' => 'boolean', 'description' => 'Daily P&L summary', 'group' => 'notifications'],
            ['key' => 'NOTIFY_BALANCE_ALERTS', 'value' => 'true', 'type' => 'boolean', 'description' => 'Low balance alerts', 'group' => 'notifications'],
            ['key' => 'NOTIFY_ERRORS', 'value' => 'true', 'type' => 'boolean', 'description' => 'Error alerts', 'group' => 'notifications'],
            ['key' => 'NOTIFY_WEEKLY_REPORT', 'value' => 'true', 'type' => 'boolean', 'description' => 'Weekly report', 'group' => 'notifications'],
            ['key' => 'NOTIFY_EACH_TRADE', 'value' => 'false', 'type' => 'boolean', 'description' => 'Per-trade notifications', 'group' => 'notifications'],
            ['key' => 'NOTIFY_AI_AUDITS', 'value' => 'true', 'type' => 'boolean', 'description' => 'AI audit notifications', 'group' => 'notifications'],
            ['key' => 'LOW_BALANCE_THRESHOLD', 'value' => '20', 'type' => 'decimal', 'description' => 'Low balance alert threshold', 'group' => 'notifications'],
            ['key' => 'DRAWDOWN_ALERT_PERCENTAGE', 'value' => '25', 'type' => 'decimal', 'description' => 'Drawdown alert %', 'group' => 'notifications'],
        ];
    }

    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'number' => (int) $value,
            'decimal' => $value,
            'boolean' => in_array(strtolower($value), ['true', '1', 'yes'], true),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    private function groupCacheKey(int|string|null $userId, string $group): string
    {
        return self::CACHE_PREFIX . $userId . ':group.' . $group . ':v' . self::DEFAULTS_SCHEMA_VERSION;
    }
}
