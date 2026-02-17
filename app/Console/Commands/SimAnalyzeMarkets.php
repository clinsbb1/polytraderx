<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\AIRouter;
use App\Services\Polymarket\MarketService;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SimAnalyzeMarkets extends Command
{
    protected $signature = 'sim:analyze-markets';
    protected $description = 'Run AI Muscles analysis on markets approaching entry window';

    public function handle(
        UserBotRunner $runner,
        MarketService $marketService,
        PriceAggregator $priceAggregator,
        AIRouter $aiRouter,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($marketService, $priceAggregator, $aiRouter) {
            $markets = $marketService->getMarketsEndingSoon(withinSeconds: 300, userId: $user->id);

            $analyzed = 0;
            foreach ($markets as $market) {
                $secondsRemaining = $market['seconds_remaining'] ?? 0;

                // Pre-score markets 1-5 minutes from close (before entry window)
                if ($secondsRemaining < 60 || $secondsRemaining > 300) {
                    continue;
                }

                $polymarketPrices = [
                    'yes_price' => $market['yes_price'],
                    'no_price' => $market['no_price'],
                ];
                $spotData = $priceAggregator->getMarketContext($market['asset'], $polymarketPrices, $user->id);

                $result = $aiRouter->getMusclesAnalysis($market, $spotData, $user->id);

                if (is_array($result) && isset($result['confidence'])) {
                    $cacheKey = "muscles:{$user->id}:{$market['condition_id']}";
                    Cache::put($cacheKey, $result, 300);
                    $analyzed++;
                }
            }

            return ['markets_found' => $markets->count(), 'analyzed' => $analyzed];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $this->info("User #{$userId}: {$r['markets_found']} markets, {$r['analyzed']} pre-analyzed");
            }
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
