<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BalanceSnapshot;
use App\Services\Settings\SettingsService;
use App\Services\Telegram\NotificationService;
use App\Services\Trading\SimulationBalanceService;
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
        SimulationBalanceService $balanceService,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($settings, $notifications, $balanceService) {
            $isDryRun = true; // Simulation-only platform: balance snapshots are always simulated.
            $snapshot = $balanceService->snapshotForUser($user->id);
            $balance = (float) $snapshot->balance_usdc;
            $positionsValue = (float) $snapshot->open_positions_value;
            $totalEquity = (float) $snapshot->total_equity;

            // Auto-pause simulator when balance is depleted.
            $simulatorEnabled = $settings->getBool('SIMULATOR_ENABLED', false, $user->id);
            if ($simulatorEnabled && $balance <= 0.0) {
                $settings->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);
                $notifications->notifyBotPaused(
                    'Balance reached $0.00. Simulator has been turned off to prevent further simulated entries.',
                    $user
                );
            }

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
