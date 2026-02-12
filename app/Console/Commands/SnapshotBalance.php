<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BalanceSnapshot;
use App\Models\Trade;
use App\Services\Polymarket\BalanceService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\Settings\SettingsService;
use App\Services\Telegram\NotificationService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SnapshotBalance extends Command
{
    protected $signature = 'bot:snapshot-balance';
    protected $description = 'Record balance snapshots and check for alerts';

    public function handle(
        UserBotRunner $runner,
        SettingsService $settings,
        NotificationService $notifications,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($settings, $notifications) {
            $isDryRun = $settings->getBool('DRY_RUN', true, $user->id);

            $balance = 0.0;
            $positionsValue = 0.0;

            if ($isDryRun) {
                // In DRY_RUN: simulate from initial balance + trade PNLs
                $initialBalance = 100.0; // Default starting balance
                $totalPnl = (float) Trade::forUser($user->id)
                    ->whereNotNull('pnl')
                    ->sum('pnl');
                $balance = $initialBalance + $totalPnl;

                $openAmount = (float) Trade::forUser($user->id)
                    ->open()
                    ->sum('amount');
                $positionsValue = $openAmount;
            } else {
                try {
                    $client = new PolymarketClient($user);
                    $balanceService = new BalanceService($client);
                    $balance = $balanceService->getBalance();
                    $positionsValue = $balanceService->getPositionValue();
                } catch (\Exception $e) {
                    // Use last known snapshot
                    $lastSnapshot = BalanceSnapshot::forUser($user->id)
                        ->latest('snapshot_at')
                        ->first();

                    if ($lastSnapshot) {
                        $balance = (float) $lastSnapshot->balance_usdc;
                        $positionsValue = (float) $lastSnapshot->open_positions_value;
                    }

                    Log::channel('bot')->warning('Balance fetch failed, using last known', [
                        'user_id' => $user->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

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
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
