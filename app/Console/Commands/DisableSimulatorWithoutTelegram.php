<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Services\Trading\SimulationBalanceService;
use Illuminate\Console\Command;

class DisableSimulatorWithoutTelegram extends Command
{
    protected $signature = 'sim:disable-without-telegram {--dry-run : Preview changes without writing to database}';
    protected $description = 'Disable simulator and cancel open/pending trades for users without Telegram linked';

    public function handle(
        SettingsService $settings,
        SimulationBalanceService $balanceService
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $users = User::query()
            ->where(function ($query): void {
                $query->whereNull('telegram_chat_id')
                    ->orWhere('telegram_chat_id', '');
            })
            ->get(['id', 'account_id']);

        if ($users->isEmpty()) {
            $this->info('No users without Telegram link found.');
            return self::SUCCESS;
        }

        $summary = [
            'users_checked' => 0,
            'simulators_disabled' => 0,
            'trades_cancelled' => 0,
            'users_with_trades_cancelled' => 0,
        ];

        foreach ($users as $user) {
            $summary['users_checked']++;

            $simEnabled = $settings->getBool('SIMULATOR_ENABLED', false, $user->id);
            $openTradeQuery = Trade::forUser($user->id)
                ->whereIn('status', ['open', 'pending']);
            $openTradeCount = (clone $openTradeQuery)->count();

            if ($simEnabled) {
                $summary['simulators_disabled']++;
                if (!$dryRun) {
                    $settings->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);
                }
            }

            if ($openTradeCount > 0) {
                $summary['trades_cancelled'] += $openTradeCount;
                $summary['users_with_trades_cancelled']++;

                if (!$dryRun) {
                    $openTradeQuery->update([
                        'status' => 'cancelled',
                        'resolved_at' => now(),
                        'pnl' => 0,
                    ]);

                    try {
                        $balanceService->snapshotForUser($user->id);
                    } catch (\Throwable) {
                        // Snapshot failure should not stop batch cleanup.
                    }
                }
            }

            if ($simEnabled || $openTradeCount > 0) {
                $this->line(sprintf(
                    'User %s: simulator %s, open/pending trades %d',
                    $user->account_id,
                    $simEnabled ? 'OFF' : 'already off',
                    $openTradeCount
                ));
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'DRY RUN summary:' : 'Completed summary:');
        $this->line('Users checked: ' . $summary['users_checked']);
        $this->line('Simulators disabled: ' . $summary['simulators_disabled']);
        $this->line('Trades cancelled: ' . $summary['trades_cancelled']);
        $this->line('Users with cancelled trades: ' . $summary['users_with_trades_cancelled']);

        return self::SUCCESS;
    }
}

