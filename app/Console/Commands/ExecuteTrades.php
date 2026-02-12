<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Trading\StrategyEngine;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteTrades extends Command
{
    protected $signature = 'bot:execute-trades';
    protected $description = 'Evaluate markets and execute trades for all active users';

    public function handle(UserBotRunner $runner, StrategyEngine $engine): int
    {
        $results = $runner->runForEachUser(function ($user) use ($engine) {
            return $engine->runForUser($user);
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
                continue;
            }

            $r = $result['result'];
            $skippedCount = count($r['skipped']);
            $this->info("User #{$userId}: scanned={$r['markets_scanned']} window={$r['in_entry_window']} signals={$r['signals_generated']} trades={$r['trades_placed']} skipped={$skippedCount}");

            if ($r['trades_placed'] > 0) {
                Log::channel('bot')->info("Trades placed for user #{$userId}", $r);
            }
        }

        if (empty($results)) {
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
