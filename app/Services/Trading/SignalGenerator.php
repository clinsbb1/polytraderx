<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\AI\ReflexesService;
use App\Services\Settings\SettingsService;

class SignalGenerator
{
    public function __construct(
        private ReflexesService $reflexes,
        private RiskManager $riskManager,
        private SettingsService $settings,
    ) {}

    public function generateSignal(
        array $market,
        array $spotData,
        int $userId,
        float $currentBankroll,
        ?array $musclesResult = null,
    ): array {
        // 1. Risk check
        $riskCheck = $this->riskManager->canTrade($userId);
        if (!$riskCheck['allowed']) {
            return [
                'action' => 'SKIP',
                'side' => null,
                'confidence' => 0.0,
                'bet_amount' => 0.0,
                'decision_tier' => 'reflexes',
                'reasoning' => 'Risk check failed: ' . $riskCheck['reason'],
                'reflexes_result' => null,
                'muscles_result' => null,
                'risk_check' => $riskCheck,
            ];
        }

        // 2. Reflexes evaluation
        $reflexesResult = $this->reflexes->evaluate($market, $spotData, $userId);
        if ($reflexesResult['action'] === 'SKIP') {
            return [
                'action' => 'SKIP',
                'side' => null,
                'confidence' => 0.0,
                'bet_amount' => 0.0,
                'decision_tier' => 'reflexes',
                'reasoning' => 'Reflexes: ' . $reflexesResult['reason'],
                'reflexes_result' => $reflexesResult,
                'muscles_result' => null,
                'risk_check' => $riskCheck,
            ];
        }

        // 3. Determine confidence
        $decisionTier = 'reflexes';
        if ($musclesResult !== null && isset($musclesResult['confidence'])) {
            // Muscles says SKIP or high reversal risk → respect it
            $musclesSide = $musclesResult['side'] ?? null;
            $musclesReversal = $musclesResult['reversal_risk'] ?? '';

            if ($musclesSide === 'SKIP' || $musclesReversal === 'high') {
                return [
                    'action' => 'SKIP',
                    'side' => null,
                    'confidence' => (float) $musclesResult['confidence'],
                    'bet_amount' => 0.0,
                    'decision_tier' => 'muscles',
                    'reasoning' => 'Muscles AI: ' . ($musclesSide === 'SKIP' ? 'recommended SKIP' : 'high reversal risk'),
                    'reflexes_result' => $reflexesResult,
                    'muscles_result' => $musclesResult,
                    'risk_check' => $riskCheck,
                ];
            }

            $confidence = (float) $musclesResult['confidence'];
            $decisionTier = 'muscles';
        } else {
            // Estimate from reflexes: 1 - reversal probability
            $reversalProb = $reflexesResult['details']['reversal_probability'] ?? 0.05;
            $confidence = 1.0 - $reversalProb;
        }

        // 4. Check minimum confidence
        $minConfidence = $this->normalizeUnitInterval(
            $this->settings->getFloat('MIN_CONFIDENCE_SCORE', 0.92, $userId)
        );
        if ($confidence < $minConfidence) {
            return [
                'action' => 'SKIP',
                'side' => $reflexesResult['side'],
                'confidence' => $confidence,
                'bet_amount' => 0.0,
                'decision_tier' => $decisionTier,
                'reasoning' => "Confidence {$confidence} below minimum {$minConfidence}",
                'reflexes_result' => $reflexesResult,
                'muscles_result' => $musclesResult,
                'risk_check' => $riskCheck,
            ];
        }

        // 5. Calculate bet size
        $betAmount = $this->riskManager->calculateBetSize($confidence, $currentBankroll, $userId);
        if (!is_finite($betAmount) || $betAmount <= 0) {
            return [
                'action' => 'SKIP',
                'side' => $reflexesResult['side'],
                'confidence' => round($confidence, 4),
                'bet_amount' => 0.0,
                'decision_tier' => $decisionTier,
                'reasoning' => 'Calculated bet size is invalid (non-positive), skipping trade.',
                'reflexes_result' => $reflexesResult,
                'muscles_result' => $musclesResult,
                'risk_check' => $riskCheck,
            ];
        }

        return [
            'action' => 'EXECUTE',
            'side' => $reflexesResult['side'],
            'confidence' => round($confidence, 4),
            'bet_amount' => $betAmount,
            'decision_tier' => $decisionTier,
            'reasoning' => "Signal: BUY_{$reflexesResult['side']} on {$market['asset']} @ confidence {$confidence}",
            'reflexes_result' => $reflexesResult,
            'muscles_result' => $musclesResult,
            'risk_check' => $riskCheck,
        ];
    }

    private function normalizeUnitInterval(float $value): float
    {
        if (!is_finite($value)) {
            return 0.0;
        }

        // Accept both ratio style (0.92) and percent style (92).
        if ($value > 1.0 && $value <= 100.0) {
            $value /= 100.0;
        }

        return max(0.0, min(1.0, $value));
    }
}
