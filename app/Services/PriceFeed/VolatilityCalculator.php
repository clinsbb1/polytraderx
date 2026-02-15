<?php

declare(strict_types=1);

namespace App\Services\PriceFeed;

use Illuminate\Support\Facades\Log;

class VolatilityCalculator
{
    public function __construct(private BinanceService $binance) {}

    public function estimate1MinVolatility(string $asset): float
    {
        try {
            $changes = $this->binance->get1MinChanges($asset, 10);

            if (count($changes) < 2) {
                return 0.0;
            }

            return $this->standardDeviation($changes);
        } catch (\Exception $e) {
            Log::channel('simulator')->warning('Failed to estimate volatility', [
                'asset' => $asset,
                'message' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    public function estimateReversalProbability(
        string $asset,
        float $currentChangePct,
        int $secondsRemaining,
    ): float {
        if ($secondsRemaining <= 0) {
            return 0.0;
        }

        $vol1Min = $this->estimate1MinVolatility($asset);

        if ($vol1Min <= 0.0) {
            // Can't estimate — assume moderate reversal risk
            return 0.2;
        }

        // Scale 1-min volatility to remaining seconds
        $volRemaining = $vol1Min * sqrt($secondsRemaining / 60.0);

        if ($volRemaining <= 0.0) {
            return 0.0;
        }

        // Probability that price reverses past zero change
        // = probability that a normal variable drops by |currentChangePct|
        $zScore = abs($currentChangePct) / $volRemaining;

        // P(reversal) = normalCDF(-z)
        return $this->normalCdf(-$zScore);
    }

    public function isVolatilityExtreme(string $asset): bool
    {
        try {
            $recentChanges = $this->binance->get1MinChanges($asset, 5);
            $hourChanges = $this->binance->get1MinChanges($asset, 60);

            if (count($recentChanges) < 2 || count($hourChanges) < 5) {
                return false;
            }

            $recentVol = $this->standardDeviation($recentChanges);
            $hourVol = $this->standardDeviation($hourChanges);

            if ($hourVol <= 0.0) {
                return false;
            }

            return $recentVol > (2.0 * $hourVol);
        } catch (\Exception $e) {
            Log::channel('simulator')->warning('Failed to check extreme volatility', [
                'asset' => $asset,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function standardDeviation(array $values): float
    {
        $count = count($values);

        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $sumSquaredDiffs = 0.0;

        foreach ($values as $value) {
            $sumSquaredDiffs += ($value - $mean) ** 2;
        }

        return sqrt($sumSquaredDiffs / ($count - 1));
    }

    private function normalCdf(float $x): float
    {
        // Abramowitz and Stegun approximation
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x) / sqrt(2);
        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return 0.5 * (1.0 + $sign * $y);
    }
}
