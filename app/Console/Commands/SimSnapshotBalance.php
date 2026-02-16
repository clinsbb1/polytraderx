<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BalanceSnapshot;
use App\Models\Trade;
use App\Services\Settings\SettingsService;
use App\Services\Telegram\NotificationService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class SimSnapshotBalance extends Command
{
    protected $signature = 'sim:snapshot-balance';
    protected $description = 'Record balance snapshots and check for alerts';

    public function handle(
        UserBotRunner $runner,
        SettingsService $settings,
        NotificationService $notifications,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($settings, $notifications) {
            $isDryRun = true; // Simulation-only platform: balance snapshots are always simulated.

            $balance = 0.0;
            $positionsValue = 0.0;

            // Simulate from initial balance + resolved trade PNL + open exposure.
            $initialBalance = 100.0; // Default starting balance
            $totalPnl = (float) Trade::forUser($user->id)
                ->whereNotNull('pnl')
                ->sum('pnl');
            $balance = $initialBalance + $totalPnl;

            $openAmount = (float) Trade::forUser($user->id)
                ->open()
                ->sum('amount');
            $positionsValue = $openAmount;

            $totalEquity = $balance + $positionsValue;

            $snapshot = BalanceSnapshot::create([
                'user_id' => $user->id,
                'balance_usdc' => $balance,
                'open_positions_value' => $positionsValue,
                'total_equity' => $totalEquity,
                'snapshot_at' => now(),
                'created_at' => now(),
            ]);

            // Check low balance alert
            $lowThreshold = $settings->getFloat('LOW_BALANCE_THRESHOLD', 20.0, $user->id);
            if ($balance < $lowThreshold && $balance > 0) {
                $notifications->notifyBalanceAlert($balance, $user);
            }

            // Check drawdown alert
            $drawdownThreshold = $settings->getFloat('DRAWDOWN_ALERT_PERCENTAGE', 25.0, $user->id);
            $startOfDaySnapshot = BalanceSnapshot::forUser($user->id)
                ->whereDate('snapshot_at', today())
                ->orderBy('snapshot_at', 'asc')
                ->first();

            if ($startOfDaySnapshot) {
                $startBalance = (float) $startOfDaySnapshot->total_equity;
                if ($startBalance > 0 && $totalEquity < $startBalance) {
                    $drawdownPct = (($startBalance - $totalEquity) / $startBalance) * 100;
                    if ($drawdownPct >= $drawdownThreshold) {
                        $dailyPnl = $totalEquity - $startBalance;
                        $notifications->notifyDrawdownAlert($dailyPnl, $drawdownPct, $user);
                    }
                }
            }

            return [
                'balance' => $balance,
                'positions' => $positionsValue,
                'equity' => $totalEquity,
                'dry_run' => $isDryRun,
            ];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $mode = $r['dry_run'] ? 'DRY' : 'LIVE';
                $this->info("User #{$userId}: \${$r['equity']} equity ({$mode})");
            }
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
