<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Polymarket\MarketService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\Trading\MarketTimingService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanMarkets extends Command
{
    protected $signature = 'bot:scan-markets';
    protected $description = 'Scan for active 15-minute crypto prediction markets';

    public function handle(
        UserBotRunner $runner,
        MarketService $marketService,
        MarketTimingService $timingService,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($marketService, $timingService) {
            $client = new PolymarketClient($user);
            $markets = $marketService->getActiveCryptoMarkets($client);
            $entryWindows = $timingService->getActiveEntryWindows($markets, $user->id);

            $assets = $markets->pluck('asset')->unique()->values()->toArray();

            Log::channel('bot')->info("Market scan for {$user->account_id}", [
                'total_markets' => $markets->count(),
                'in_entry_window' => $entryWindows->count(),
                'assets' => $assets,
            ]);

            return [
                'total_markets' => $markets->count(),
                'in_entry_window' => $entryWindows->count(),
                'assets' => $assets,
            ];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $this->info("User #{$userId}: {$r['total_markets']} markets found, {$r['in_entry_window']} in entry window [" . implode(',', $r['assets']) . "]");
            }
        }

        if (empty($results)) {
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
