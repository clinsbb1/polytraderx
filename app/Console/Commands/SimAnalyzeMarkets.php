<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\AIRouter;
use App\Services\Polymarket\MarketService;
use App\Services\PriceFeed\PriceAggregator;
use App\Services\Settings\PlatformSettingsService;
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
        PlatformSettingsService $platformSettings,
        AIRouter $aiRouter,
    ): int {
        $preAnalysisEnabled = $platformSettings->getBool('AI_PRE_ANALYSIS_ENABLED', false);
        if (!$preAnalysisEnabled) {
            $this->info('AI pre-analysis is disabled (AI_PRE_ANALYSIS_ENABLED=false).');
            return Command::SUCCESS;
        }

        $maxCandidates = max(1, min(20, $platformSettings->getInt('AI_PRE_ANALYSIS_MAX_CANDIDATES', 3)));

        $results = $runner->runForEachUser(function ($user) use ($marketService, $priceAggregator, $aiRouter, $maxCandidates) {
            $markets = $marketService->getMarketsEndingSoon(withinSeconds: 300, userId: $user->id);
            $candidates = $markets
                ->filter(function ($market) {
                    $secondsRemaining = (int) ($market['seconds_remaining'] ?? 0);
                    return $secondsRemaining >= 60 && $secondsRemaining <= 300;
                })
                ->sortBy(fn($m) => (int) ($m['seconds_remaining'] ?? PHP_INT_MAX))
                // Keep cost predictable: pre-analyze only a small nearest subset per cycle.
                ->take($maxCandidates)
                ->values();

            $analyzed = 0;
            $cachedHits = 0;
            foreach ($candidates as $market) {
                $conditionId = (string) ($market['condition_id'] ?? '');
                if ($conditionId === '') {
                    continue;
                }

                $cacheKey = "muscles:{$user->id}:{$conditionId}";
                if (Cache::has($cacheKey)) {
                    $cachedHits++;
                    continue;
                }

                $polymarketPrices = [
                    'yes_price' => $market['yes_price'],
                    'no_price' => $market['no_price'],
                ];
                $spotData = $priceAggregator->getMarketContext($market['asset'], $polymarketPrices, $user->id);

                $result = $aiRouter->getMusclesAnalysis($market, $spotData, $user->id);

                if (is_array($result) && isset($result['confidence'])) {
                    $analyzed++;
                }
            }

            return [
                'markets_found' => $markets->count(),
                'candidates' => $candidates->count(),
                'analyzed' => $analyzed,
                'cache_hits' => $cachedHits,
                'max_candidates' => $maxCandidates,
            ];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $this->info(
                    "User #{$userId}: {$r['markets_found']} markets, {$r['candidates']} candidates (max {$r['max_candidates']}), {$r['analyzed']} pre-analyzed, {$r['cache_hits']} cached"
                );
            }
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
