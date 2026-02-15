<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\BalanceSnapshot;
use App\Models\Trade;
use App\Models\User;

class StrategyMetrics
{
    /**
     * Calculate maximum drawdown over a given period
     *
     * @param User $user
     * @param int $days Number of days to look back
     * @return array ['percent' => float, 'amount' => float, 'peak' => Carbon|null, 'trough' => Carbon|null]
     */
    public function calculateMaxDrawdown(User $user, int $days = 30): array
    {
        $snapshots = BalanceSnapshot::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();

        if ($snapshots->count() < 2) {
            return ['percent' => 0.0, 'amount' => 0.0, 'peak' => null, 'trough' => null];
        }

        $peak = (float) $snapshots->first()->total_equity;
        $peakDate = $snapshots->first()->created_at;
        $maxDrawdown = 0.0;
        $maxDrawdownAmount = 0.0;
        $troughDate = null;

        foreach ($snapshots as $snapshot) {
            $equity = (float) $snapshot->total_equity;

            if ($equity > $peak) {
                $peak = $equity;
                $peakDate = $snapshot->created_at;
            }

            if ($peak > 0) {
                $drawdown = (($peak - $equity) / $peak) * 100;

                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                    $maxDrawdownAmount = $peak - $equity;
                    $troughDate = $snapshot->created_at;
                }
            }
        }

        return [
            'percent' => round($maxDrawdown, 2),
            'amount' => round($maxDrawdownAmount, 2),
            'peak' => $peakDate,
            'trough' => $troughDate,
        ];
    }

    /**
     * Calculate consistency score based on win rate stability
     *
     * @param User $user
     * @param int $days Number of days to analyze
     * @return float Score from 0.0-100.0
     */
    public function calculateConsistencyScore(User $user, int $days = 30): float
    {
        $trades = Trade::where('user_id', $user->id)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays($days))
            ->orderBy('resolved_at')
            ->get();

        if ($trades->count() < 10) {
            return 0.0; // Insufficient data
        }

        // Calculate win rates in 7-day rolling windows
        $windowSize = 7;
        $winRates = [];
        $currentDate = now()->subDays($days);
        $endDate = now();

        while ($currentDate->lte($endDate)) {
            $windowTrades = $trades->filter(function ($trade) use ($currentDate, $windowSize) {
                return $trade->resolved_at->gte($currentDate) &&
                       $trade->resolved_at->lte($currentDate->copy()->addDays($windowSize));
            });

            if ($windowTrades->count() >= 3) {
                $wins = $windowTrades->where('status', 'won')->count();
                $winRates[] = ($wins / $windowTrades->count()) * 100;
            }

            $currentDate->addDays(1);
        }

        if (count($winRates) < 2) {
            return 0.0;
        }

        // Lower standard deviation = higher consistency
        $mean = array_sum($winRates) / count($winRates);
        $variance = array_sum(array_map(fn($x) => ($x - $mean) ** 2, $winRates)) / count($winRates);
        $stdDev = sqrt($variance);

        // Convert std dev to score (lower std dev = higher score)
        // Std dev of 0 = 100, std dev of 50 = 0
        $score = max(0, 100 - ($stdDev * 2));

        return round($score, 2);
    }

    /**
     * Find win/loss streaks
     *
     * @param User $user
     * @return array ['current' => int, 'longest_win' => int, 'longest_loss' => int]
     */
    public function findWinStreaks(User $user): array
    {
        $trades = Trade::where('user_id', $user->id)
            ->whereNotNull('resolved_at')
            ->whereIn('status', ['won', 'lost'])
            ->orderBy('resolved_at')
            ->get();

        if ($trades->isEmpty()) {
            return ['current' => 0, 'longest_win' => 0, 'longest_loss' => 0];
        }

        $currentStreak = 0;
        $longestWin = 0;
        $longestLoss = 0;
        $tempStreak = 0;
        $lastStatus = null;

        foreach ($trades as $trade) {
            if ($trade->status === $lastStatus) {
                $tempStreak++;
            } else {
                // Streak ended, check if it was a record
                if ($lastStatus === 'won' && $tempStreak > $longestWin) {
                    $longestWin = $tempStreak;
                } elseif ($lastStatus === 'lost' && $tempStreak > $longestLoss) {
                    $longestLoss = $tempStreak;
                }

                $tempStreak = 1;
                $lastStatus = $trade->status;
            }
        }

        // Check final streak
        if ($lastStatus === 'won' && $tempStreak > $longestWin) {
            $longestWin = $tempStreak;
        } elseif ($lastStatus === 'lost' && $tempStreak > $longestLoss) {
            $longestLoss = $tempStreak;
        }

        // Current streak (positive for wins, negative for losses)
        $currentStreak = $lastStatus === 'won' ? $tempStreak : -$tempStreak;

        return [
            'current' => $currentStreak,
            'longest_win' => $longestWin,
            'longest_loss' => $longestLoss,
        ];
    }

    /**
     * Assess overall strategy stability
     *
     * @param User $user
     * @param int $days Number of days to analyze
     * @return string 'excellent' | 'good' | 'moderate' | 'poor' | 'insufficient_data'
     */
    public function assessStability(User $user, int $days = 30): string
    {
        $trades = Trade::where('user_id', $user->id)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays($days))
            ->get();

        if ($trades->count() < 20) {
            return 'insufficient_data';
        }

        $drawdown = $this->calculateMaxDrawdown($user, $days);
        $consistency = $this->calculateConsistencyScore($user, $days);

        $wins = $trades->where('status', 'won')->count();
        $winRate = ($wins / $trades->count()) * 100;

        // Scoring criteria
        $score = 0;

        // Drawdown score (max 40 points)
        if ($drawdown['percent'] < 5) {
            $score += 40;
        } elseif ($drawdown['percent'] < 10) {
            $score += 30;
        } elseif ($drawdown['percent'] < 20) {
            $score += 20;
        } elseif ($drawdown['percent'] < 30) {
            $score += 10;
        }

        // Consistency score (max 30 points)
        $score += ($consistency / 100) * 30;

        // Win rate score (max 30 points)
        if ($winRate > 60) {
            $score += 30;
        } elseif ($winRate > 55) {
            $score += 20;
        } elseif ($winRate > 50) {
            $score += 10;
        }

        // Classify based on total score
        if ($score >= 80) {
            return 'excellent';
        } elseif ($score >= 60) {
            return 'good';
        } elseif ($score >= 40) {
            return 'moderate';
        } else {
            return 'poor';
        }
    }
}
