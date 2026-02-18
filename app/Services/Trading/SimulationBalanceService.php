<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\BalanceSnapshot;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SimulationBalanceService
{
    /**
     * Calculate simulated cash + exposure + equity from trade cashflows.
     *
     * Accounting model:
     * - Opening a trade reserves/spends `amount` immediately.
     * - A won trade returns `potential_payout`, a lost trade returns `0`.
     * - Open/pending exposure is tracked separately as open_positions_value.
     */
    public function calculateForUser(int $userId): array
    {
        [$anchorBalance, $anchorTime] = $this->anchorForUser($userId);

        $totalPlaced = (float) $this->baseTradesQuery($userId, $anchorTime)
            ->whereIn('status', ['pending', 'open', 'won', 'lost'])
            ->sum('amount');

        $totalWonPayout = (float) $this->baseTradesQuery($userId, $anchorTime)
            ->where('status', 'won')
            ->sum('potential_payout');

        $openExposure = (float) $this->baseTradesQuery($userId, $anchorTime)
            ->whereIn('status', ['pending', 'open'])
            ->sum('amount');

        $cashBalance = $anchorBalance - $totalPlaced + $totalWonPayout;
        $totalEquity = $cashBalance + $openExposure;

        return [
            'balance' => round($cashBalance, 2),
            'positions' => round($openExposure, 2),
            'equity' => round($totalEquity, 2),
            'anchor_balance' => round($anchorBalance, 2),
            'anchor_time' => $anchorTime?->toIso8601String(),
        ];
    }

    public function snapshotForUser(User|int $user): BalanceSnapshot
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;
        $state = $this->calculateForUser($userId);

        return BalanceSnapshot::create([
            'user_id' => $userId,
            'balance_usdc' => $state['balance'],
            'open_positions_value' => $state['positions'],
            'total_equity' => $state['equity'],
            'snapshot_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function baseTradesQuery(int $userId, ?Carbon $anchorTime): Builder
    {
        return Trade::forUser($userId)
            ->when(
                $anchorTime !== null,
                function (Builder $query) use ($anchorTime): void {
                    $query->where(function (Builder $q) use ($anchorTime): void {
                        $q->where('entry_at', '>=', $anchorTime)
                            ->orWhere(function (Builder $q2) use ($anchorTime): void {
                                $q2->whereNull('entry_at')
                                    ->where('created_at', '>=', $anchorTime);
                            });
                    });
                }
            );
    }

    private function anchorForUser(int $userId): array
    {
        $firstSnapshot = BalanceSnapshot::forUser($userId)
            ->orderBy('snapshot_at')
            ->first();

        if ($firstSnapshot) {
            return [
                (float) $firstSnapshot->balance_usdc,
                $firstSnapshot->snapshot_at,
            ];
        }

        return [100.0, null];
    }
}

