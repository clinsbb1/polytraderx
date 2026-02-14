<?php

declare(strict_types=1);

namespace App\Services\RateLimiter;

use Illuminate\Support\Facades\Cache;

class ApiRateLimiter
{
    /**
     * Throttle API calls to a service. Sleeps if rate exceeded.
     */
    public function throttle(string $service, int $maxPerMinute, callable $callback): mixed
    {
        $key = "api_rate_limit:{$service}";
        $count = (int) Cache::get($key, 0);

        if ($count >= $maxPerMinute) {
            $ttl = (int) Cache::get("{$key}:ttl", 0);
            $sleepSeconds = max(1, $ttl - time());
            sleep(min($sleepSeconds, 60));
            Cache::forget($key);
        }

        if (!Cache::has($key)) {
            Cache::put($key, 1, 60);
            Cache::put("{$key}:ttl", time() + 60, 65);
        } else {
            Cache::increment($key);
        }

        return $callback();
    }

    /**
     * Check if a service is currently rate limited.
     */
    public function isLimited(string $service, int $maxPerMinute): bool
    {
        $key = "api_rate_limit:{$service}";
        return (int) Cache::get($key, 0) >= $maxPerMinute;
    }
}
