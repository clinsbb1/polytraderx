<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\Settings\SettingsService;

class StrategyUpdater
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Apply a single fix from an AI audit suggestion.
     *
     * @param int    $userId    The user whose strategy params to update.
     * @param string $param     The strategy param key (e.g. MIN_CONFIDENCE_SCORE).
     * @param string $suggested The new value to set.
     */
    public function applyFix(int $userId, string $param, string $suggested): void
    {
        $this->settings->set($param, $suggested, 'ai_audit', $userId);
    }
}
