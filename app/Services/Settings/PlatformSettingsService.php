<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\PlatformSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsService
{
    private const CACHE_PREFIX = 'platform:';
    private const CACHE_TTL = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $setting = PlatformSetting::where('key', $key)->first();

                if (!$setting) {
                    return $default;
                }

                return $this->castValue($setting->value, $setting->type);
            }
        );
    }

    public function set(string $key, mixed $value): void
    {
        $setting = PlatformSetting::where('key', $key)->first();

        if (!$setting) {
            return;
        }

        $setting->update(['value' => (string) $value]);

        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget(self::CACHE_PREFIX . 'group.' . $setting->group);
    }

    public function getGroup(string $group): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'group.' . $group,
            self::CACHE_TTL,
            function () use ($group) {
                return PlatformSetting::where('group', $group)
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

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
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
}
