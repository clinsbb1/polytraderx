<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\StrategyParam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_PREFIX = 'settings.';
    private const CACHE_TTL = 3600; // 1 hour

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $param = StrategyParam::where('key', $key)->first();

                if (!$param) {
                    return $default;
                }

                return $this->castValue($param->value, $param->type);
            }
        );
    }

    public function set(string $key, mixed $value, string $updatedBy = 'system'): void
    {
        $param = StrategyParam::where('key', $key)->first();

        if (!$param) {
            return;
        }

        $param->update([
            'previous_value' => $param->value,
            'value' => (string) $value,
            'updated_by' => $updatedBy,
        ]);

        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget(self::CACHE_PREFIX . 'group.' . $param->group);
    }

    public function getGroup(string $group): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'group.' . $group,
            self::CACHE_TTL,
            function () use ($group) {
                return StrategyParam::where('group', $group)
                    ->orderBy('key')
                    ->get();
            }
        );
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['true', '1', 'yes'], true);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);

        return (float) $value;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return (int) $value;
    }

    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'number' => (int) $value,
            'decimal' => $value, // Keep as string for bcmath compatibility
            'boolean' => in_array(strtolower($value), ['true', '1', 'yes'], true),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
