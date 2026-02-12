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
        $entryWindow = $this->settings->getInt('ENTRY_WINDOW_SECONDS', 60, $userId);

        return $secondsRemaining <= $entryWindow
            && $secondsRemaining > 5;
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
