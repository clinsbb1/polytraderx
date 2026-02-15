<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Settings\SettingsService;
use App\Services\Telegram\TelegramBotService;
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
        TelegramBotService $telegram,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($engine, $settings, $telegram) {
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
                    $telegram->sendToUser($user->id, $message);
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
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
