<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Collection;

class MarketTimingService
{
    public function __construct(private SettingsService $settings) {}

    public function isInEntryWindow(array $market, int $userId): bool
    {
        $secondsRemaining = $this->getSecondsRemaining($market);
        $window = $this->getEntryWindowRange($userId);

        return $secondsRemaining >= $window['min']
            && $secondsRemaining <= $window['max'];
    }

    /**
     * Resolve and normalize user-configured entry window range.
     *
     * Backward compatibility:
     * - If new min/max keys are missing, legacy ENTRY_WINDOW_SECONDS remains the max bound.
     */
    public function getEntryWindowRange(int $userId): array
    {
        $legacyMax = $this->settings->getInt('ENTRY_WINDOW_SECONDS', 60, $userId);

        $min = $this->settings->getInt('ENTRY_WINDOW_MIN_SECONDS', 5, $userId);
        $max = $this->settings->getInt('ENTRY_WINDOW_MAX_SECONDS', max(5, $legacyMax), $userId);

        $min = max(5, min(900, $min));
        $max = max(5, min(900, $max));

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    public function getSecondsRemaining(array $market): int
    {
        $endTime = $market['end_time'] ?? null;

        if ($endTime === null) {
            return 0;
        }

        $remaining = (int) now()->diffInSeconds($endTime, false);

        return max(0, $remaining);
    }

    public function isMarketExpired(array $market): bool
    {
        return $this->getSecondsRemaining($market) <= 0;
    }

    public function getActiveEntryWindows(Collection $markets, int $userId): Collection
    {
        return $markets->filter(fn(array $market) => $this->isInEntryWindow($market, $userId))->values();
    }
}
