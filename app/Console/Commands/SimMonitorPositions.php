<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendTelegramMessageJob;
use App\Services\Settings\SettingsService;
use App\Services\Trading\StrategyEngine;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class SimMonitorPositions extends Command
{
    protected $signature = 'sim:monitor-positions';
    protected $description = 'Check open positions and resolve completed trades';

    public function handle(
        UserBotRunner $runner,
        StrategyEngine $engine,
        SettingsService $settings,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($engine, $settings) {
            $summary = $engine->checkResolutions($user);

            // Send Telegram notifications for resolved trades
            if ($summary['resolved'] > 0 && $user->hasTelegramLinked()) {
                $notifyEach = $settings->getBool('NOTIFY_EACH_TRADE', false, $user->id);

                if ($notifyEach) {
                    $won = $summary['won'];
                    $lost = $summary['lost'];
                    $message = "Trade update: {$summary['resolved']} resolved";
                    if ($won > 0) {
                        $message .= " | {$won} won";
                    }
                    if ($lost > 0) {
                        $message .= " | {$lost} lost";
                    }
                    SendTelegramMessageJob::dispatch($user->id, $message)
                        ->onQueue((string) config('services.queues.telegram', 'telegram'));
                }
            }

            return $summary;
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
                continue;
            }

            $r = $result['result'];
            $this->info("User #{$userId}: checked={$r['checked']} resolved={$r['resolved']} won={$r['won']} lost={$r['lost']}");
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
