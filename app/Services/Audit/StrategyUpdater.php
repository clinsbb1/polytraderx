<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AiAudit;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class StrategyUpdater
{
    private const VALID_PARAMS = [
        'MAX_BET_AMOUNT' => ['min' => 0.01, 'max' => 1000.0, 'type' => 'float'],
        'MAX_BET_PERCENTAGE' => ['min' => 0.1, 'max' => 100.0, 'type' => 'float'],
        'MAX_DAILY_LOSS' => ['min' => 1.0, 'max' => 10000.0, 'type' => 'float'],
        'MAX_DAILY_TRADES' => ['min' => 1, 'max' => 500, 'type' => 'int'],
        'MAX_CONCURRENT_POSITIONS' => ['min' => 1, 'max' => 50, 'type' => 'int'],
        'MIN_CONFIDENCE_SCORE' => ['min' => 0.5, 'max' => 1.0, 'type' => 'float'],
        'MIN_ENTRY_PRICE_THRESHOLD' => ['min' => 0.5, 'max' => 1.0, 'type' => 'float'],
        'MAX_ENTRY_PRICE_THRESHOLD' => ['min' => 0.01, 'max' => 0.5, 'type' => 'float'],
        'ENTRY_WINDOW_SECONDS' => ['min' => 5, 'max' => 300, 'type' => 'int'],
        'SIMULATOR_ENABLED' => ['type' => 'bool'],
        'DRY_RUN' => ['type' => 'bool'],
    ];

    public function __construct(private SettingsService $settings) {}

    public function applyFix(AiAudit $audit, int $fixIndex, int $userId): bool
    {
        $fixes = $audit->suggested_fixes ?? [];

        if (!isset($fixes[$fixIndex])) {
            return false;
        }

        $fix = $fixes[$fixIndex];
        $paramKey = $fix['param_key'] ?? '';
        $value = $fix['suggested_value'] ?? null;

        if (!$this->validateFixValue($paramKey, $value)) {
            Log::channel('simulator')->warning('Fix rejected: invalid value', [
                'audit_id' => $audit->id,
                'param' => $paramKey,
                'value' => $value,
            ]);
            return false;
        }

        $this->settings->set($paramKey, $value, 'ai_audit', $userId);

        // Update fix status in the audit record
        $fixes[$fixIndex]['applied'] = true;
        $fixes[$fixIndex]['applied_at'] = now()->toIso8601String();
        $audit->update(['suggested_fixes' => $fixes]);

        $allApplied = collect($fixes)->every(fn($f) => ($f['applied'] ?? false) || ($f['rejected'] ?? false));
        if ($allApplied) {
            $audit->update([
                'status' => 'approved',
                'applied_at' => now(),
            ]);
        }

        Log::channel('simulator')->info('Strategy fix applied', [
            'audit_id' => $audit->id,
            'param' => $paramKey,
            'value' => $value,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function rejectFix(AiAudit $audit, int $fixIndex, ?string $reason = null): bool
    {
        $fixes = $audit->suggested_fixes ?? [];

        if (!isset($fixes[$fixIndex])) {
            return false;
        }

        $fixes[$fixIndex]['rejected'] = true;
        $fixes[$fixIndex]['reject_reason'] = $reason;
        $audit->update(['suggested_fixes' => $fixes]);

        $allProcessed = collect($fixes)->every(fn($f) => ($f['applied'] ?? false) || ($f['rejected'] ?? false));
        if ($allProcessed) {
            $audit->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'review_notes' => $reason,
            ]);
        }

        return true;
    }

    public function autoApplyFixes(AiAudit $audit, int $userId): int
    {
        Log::channel('simulator')->warning('autoApplyFixes is disabled by policy', [
            'audit_id' => $audit->id,
            'user_id' => $userId,
        ]);

        return 0;
    }

    public function validateFixValue(string $paramKey, mixed $value): bool
    {
        if (!isset(self::VALID_PARAMS[$paramKey])) {
            return false;
        }

        $rules = self::VALID_PARAMS[$paramKey];

        return match ($rules['type']) {
            'float' => is_numeric($value) && (float) $value >= $rules['min'] && (float) $value <= $rules['max'],
            'int' => is_numeric($value) && (int) $value >= $rules['min'] && (int) $value <= $rules['max'],
            'bool' => in_array($value, [true, false, 'true', 'false', '1', '0', 1, 0], true),
            default => false,
        };
    }
}
