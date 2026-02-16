<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Str;

class PromptBuilder
{
    public function __construct(private SettingsService $settings) {}

    public function buildMusclesPrompt(array $market, array $spotData, int $userId, ?int $maxPromptTokens = null): array
    {
        $recentLimit = $maxPromptTokens !== null ? max(12, min(50, intdiv($maxPromptTokens, 120))) : 50;
        $lossLimit = $maxPromptTokens !== null ? max(1, min(3, intdiv($maxPromptTokens, 1200))) : 3;

        $recentTrades = Trade::forUser($userId)->latest()->take($recentLimit)->get();
        $totalRecent = $recentTrades->count();
        $wonRecent = $recentTrades->where('status', 'won')->count();
        $winRate = $totalRecent > 0 ? round(($wonRecent / $totalRecent) * 100, 1) : 0;

        $asset = $market['asset'] ?? 'UNKNOWN';
        $similarTrades = $recentTrades->filter(function ($trade) use ($asset) {
            return $trade->asset === $asset;
        });
        $similarWon = $similarTrades->where('status', 'won')->count();
        $similarTotal = $similarTrades->count();
        $similarWinRate = $similarTotal > 0 ? round(($similarWon / $similarTotal) * 100, 1) : 0;

        $lastLosses = Trade::forUser($userId)->lost()->latest()->take($lossLimit)->get();
        $lossPatterns = $lastLosses->map(function ($trade) {
            $reasoning = $trade->decision_reasoning ?? [];
            return [
                'asset' => $trade->asset,
                'side' => $trade->side,
                'confidence' => $trade->confidence_score,
                'pnl' => $trade->pnl,
            ];
        })->toArray();

        $system = 'You are a strategy simulation analyst for PolyTraderX evaluating potential trading signals on 15-minute crypto prediction markets. '
            . 'You analyze real-time market data and Binance spot prices to assess signal quality. '
            . 'Your role is to provide a confidence score (0.00-1.00) and flag any instability or regime sensitivity. '
            . 'This is simulation mode - prioritize robust signals over maximum returns. '
            . 'Only recommend a signal if highly confident (>=0.92) and conditions are stable. Otherwise, recommend SKIP. '
            . 'Respond in JSON only. No markdown, no explanation outside the JSON object.';

        $user = "MARKET ANALYSIS REQUEST\n\n"
            . "Market: {$market['question']}\n"
            . "Asset: {$asset}\n"
            . "YES Price: {$market['yes_price']}\n"
            . "NO Price: {$market['no_price']}\n"
            . "Seconds Remaining: " . ($market['seconds_remaining'] ?? 0) . "\n"
            . "Volume: " . ($market['volume'] ?? 0) . "\n\n"
            . "BINANCE SPOT DATA:\n"
            . "Current Price: \$" . ($spotData['spot_price'] ?? 0) . "\n"
            . "Price at Market Open (~15 min ago): \$" . ($spotData['price_at_open'] ?? 0) . "\n"
            . "Change Since Open: " . ($spotData['change_since_open_pct'] ?? 0) . "%\n"
            . "1-Minute Change: " . ($spotData['change_1m_pct'] ?? 0) . "%\n"
            . "5-Minute Change: " . ($spotData['change_5m_pct'] ?? 0) . "%\n\n"
            . "HISTORICAL CONTEXT:\n"
            . "Last 50 Trades Win Rate: {$winRate}% ({$wonRecent}/{$totalRecent})\n"
            . "{$asset} Specific Win Rate: {$similarWinRate}% ({$similarWon}/{$similarTotal})\n"
            . "Last 3 Loss Patterns: " . json_encode($lossPatterns) . "\n\n"
            . "Respond in JSON ONLY (no markdown, no explanation outside JSON):\n"
            . '{"side": "YES"|"NO"|"SKIP", "confidence": 0.00-1.00, "reasoning": "one sentence", "reversal_risk": "low"|"medium"|"high", "suggested_bet_size_pct": 1.0-10.0, "regime_note": "trending|ranging|volatile|unclear", "stability_flag": "stable|caution|unstable"}' . "\n"
            . 'SKIP if confidence < 0.92 or reversal_risk is "high".';

        if ($maxPromptTokens !== null) {
            $user = $this->trimToTokenBudget($user, $maxPromptTokens);
        }

        return ['system' => $system, 'user' => $user];
    }

    public function buildBrainAuditPrompt(Trade $trade, array $forensics, int $userId, ?int $maxPromptTokens = null): array
    {
        $strategyParams = $this->settings->getGroup('risk', $userId)
            ->merge($this->settings->getGroup('trading', $userId));

        $todayPnl = (float) Trade::forUser($userId)->today()->whereNotNull('pnl')->sum('pnl');
        $weekPnl = (float) Trade::forUser($userId)
            ->where('resolved_at', '>=', now()->subDays(7))
            ->whereNotNull('pnl')
            ->sum('pnl');
        $monthPnl = (float) Trade::forUser($userId)
            ->where('resolved_at', '>=', now()->subDays(30))
            ->whereNotNull('pnl')
            ->sum('pnl');

        $weekTrades = Trade::forUser($userId)->where('resolved_at', '>=', now()->subDays(7))->count();
        $weekWins = Trade::forUser($userId)->won()->where('resolved_at', '>=', now()->subDays(7))->count();
        $weekWinRate = $weekTrades > 0 ? round(($weekWins / $weekTrades) * 100, 1) : 0;

        $similarAuditLimit = $maxPromptTokens !== null ? max(1, min(3, intdiv($maxPromptTokens, 2500))) : 3;
        $similarAudits = AiAudit::forUser($userId)
            ->where('id', '!=', 0)
            ->latest()
            ->take($similarAuditLimit)
            ->get()
            ->map(fn($a) => [
                'trigger' => $a->trigger,
                'analysis' => is_string($a->analysis) ? $a->analysis : json_encode($a->analysis),
            ])
            ->toArray();

        $lossCount = Trade::forUser($userId)->lost()->count();

        $system = 'You are a senior trading strategist performing forensic analysis on a losing trade from PolyTraderX, '
            . 'a strategy simulation platform for 15-minute crypto prediction markets on Polymarket. '
            . 'Your job is to identify the root cause, categorize the failure mode, and suggest specific parameter adjustments. '
            . "\n\nIMPORTANT: This is a SIMULATION platform. All trades are paper trades used to backtest and optimize strategies. When analyzing:\n"
            . "- Consider overfitting risk: Is the strategy too optimized for recent market conditions?\n"
            . "- Assess regime sensitivity: Would this strategy fail if volatility/volume patterns shift?\n"
            . "- Note simulation limitations: Perfect fills, zero slippage assumptions.\n\n"
            . 'Suggest robust parameters over maximum historical returns. '
            . 'Respond in JSON only. No markdown, no explanation outside the JSON object.';

        $tradeLogLimit = $maxPromptTokens !== null ? max(10, min(80, intdiv($maxPromptTokens, 90))) : count($forensics['trade_logs'] ?? []);
        $concurrentLimit = $maxPromptTokens !== null ? max(3, min(20, intdiv($maxPromptTokens, 350))) : count($forensics['context']['concurrent_positions'] ?? []);
        $similarLossLimit = $maxPromptTokens !== null ? max(1, min(5, intdiv($maxPromptTokens, 1800))) : count($forensics['similar_losses'] ?? []);

        $user = "LOSS FORENSICS ANALYSIS\n\n"
            . "LOSING TRADE:\n" . json_encode($forensics['trade'] ?? [], JSON_UNESCAPED_SLASHES) . "\n\n"
            . "TRADE LOGS:\n" . json_encode(array_slice($forensics['trade_logs'] ?? [], 0, $tradeLogLimit), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "EXTERNAL DATA:\n" . json_encode($forensics['external_data'] ?? [], JSON_UNESCAPED_SLASHES) . "\n\n"
            . "CURRENT STRATEGY PARAMS:\n" . json_encode($strategyParams->toArray(), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "PERFORMANCE CONTEXT:\n"
            . "Today P&L: \${$todayPnl}\n"
            . "7-Day P&L: \${$weekPnl} | Win Rate: {$weekWinRate}%\n"
            . "30-Day P&L: \${$monthPnl}\n"
            . "Total Loss Count: {$lossCount} (this is loss #{$lossCount})\n\n"
            . "PREVIOUS AUDITS:\n" . json_encode($similarAudits, JSON_UNESCAPED_SLASHES) . "\n\n"
            . "CONCURRENT POSITIONS:\n" . json_encode(array_slice($forensics['context']['concurrent_positions'] ?? [], 0, $concurrentLimit), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "SIMILAR LOSSES:\n" . json_encode(array_slice($forensics['similar_losses'] ?? [], 0, $similarLossLimit), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "Respond in JSON ONLY:\n"
            . '{"root_cause_category": "string (e.g. volatility_spike, api_desync, insufficient_confidence, market_timing, bad_signal)", '
            . '"analysis": "2-3 sentence explanation of what went wrong", '
            . '"severity": "low|medium|high", '
            . '"suggested_fixes": [{"param_key": "PARAM_NAME", "current_value": "X", "suggested_value": "Y", "reason": "why", "action": "review_required"}], '
            . '"pattern_detected": "string or null - any recurring pattern from previous audits", '
            . '"strategy_stability": "stable|degrading|overfitting|needs_data", '
            . '"overfitting_risk": "low|medium|high", '
            . '"regime_sensitivity": "string - describe if strategy is regime-dependent", '
            . '"confidence_in_fix": 1-10, '
            . '"overall_assessment": "1 sentence summary"}';

        if ($maxPromptTokens !== null) {
            $user = $this->trimToTokenBudget($user, $maxPromptTokens);
        }

        return ['system' => $system, 'user' => $user];
    }

    public function buildDailyReviewPrompt(int $userId, ?int $maxPromptTokens = null): array
    {
        $tradeLimit = $maxPromptTokens !== null ? max(20, min(200, intdiv($maxPromptTokens, 90))) : 500;
        $todayTrades = Trade::forUser($userId)->today()->take($tradeLimit)->get();
        $todayWon = $todayTrades->where('status', 'won')->count();
        $todayLost = $todayTrades->where('status', 'lost')->count();
        $todayPnl = (float) $todayTrades->whereNotNull('pnl')->sum('pnl');

        $strategyParams = $this->settings->getGroup('risk', $userId)
            ->merge($this->settings->getGroup('trading', $userId));

        $system = 'You are a trading strategist reviewing today\'s performance for a 15-minute crypto prediction market bot. '
            . 'Assess performance, identify patterns, and suggest parameter adjustments if needed. '
            . 'Respond in JSON only.';

        $user = "DAILY PERFORMANCE REVIEW\n\n"
            . "Total Trades: {$todayTrades->count()}\n"
            . "Won: {$todayWon} | Lost: {$todayLost}\n"
            . "P&L: \${$todayPnl}\n"
            . "Win Rate: " . ($todayTrades->count() > 0 ? round(($todayWon / $todayTrades->count()) * 100, 1) : 0) . "%\n\n"
            . "TRADES:\n" . json_encode($todayTrades->map(fn($t) => [
                'asset' => $t->asset, 'side' => $t->side, 'status' => $t->status,
                'confidence' => $t->confidence_score, 'pnl' => $t->pnl,
                'decision_tier' => $t->decision_tier,
            ])->toArray(), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "CURRENT PARAMS:\n" . json_encode($strategyParams->toArray(), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "Respond in JSON ONLY:\n"
            . '{"analysis": "2-3 sentences", "suggested_param_changes": [{"param_key": "KEY", "current_value": "X", "suggested_value": "Y", "reason": "why", "action": "review_required"}], "overall_assessment": "good|acceptable|concerning|poor"}';

        if ($maxPromptTokens !== null) {
            $user = $this->trimToTokenBudget($user, $maxPromptTokens);
        }

        return ['system' => $system, 'user' => $user];
    }

    public function buildWeeklyReviewPrompt(int $userId, ?int $maxPromptTokens = null): array
    {
        $tradeLimit = $maxPromptTokens !== null ? max(30, min(300, intdiv($maxPromptTokens, 80))) : 500;
        $weekTrades = Trade::forUser($userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->take($tradeLimit)
            ->get();

        $weekWon = $weekTrades->where('status', 'won')->count();
        $weekLost = $weekTrades->where('status', 'lost')->count();
        $weekPnl = (float) $weekTrades->whereNotNull('pnl')->sum('pnl');

        $byAsset = $weekTrades->groupBy('asset')->map(fn($trades) => [
            'count' => $trades->count(),
            'won' => $trades->where('status', 'won')->count(),
            'lost' => $trades->where('status', 'lost')->count(),
            'pnl' => (float) $trades->whereNotNull('pnl')->sum('pnl'),
        ]);

        $strategyParams = $this->settings->getGroup('risk', $userId)
            ->merge($this->settings->getGroup('trading', $userId));

        $recentAudits = AiAudit::forUser($userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->take(10)
            ->get()
            ->map(fn($a) => [
                'trigger' => $a->trigger,
                'analysis' => $a->analysis,
                'status' => $a->status,
            ]);

        $system = 'You are a senior trading strategist conducting a weekly deep analysis for a 15-minute crypto prediction market bot. '
            . 'Provide strategic insights, identify trends, and recommend adjustments. '
            . 'Respond in JSON only.';

        $user = "WEEKLY PERFORMANCE REVIEW (Last 7 Days)\n\n"
            . "Total Trades: {$weekTrades->count()}\n"
            . "Won: {$weekWon} | Lost: {$weekLost}\n"
            . "P&L: \${$weekPnl}\n"
            . "Win Rate: " . ($weekTrades->count() > 0 ? round(($weekWon / $weekTrades->count()) * 100, 1) : 0) . "%\n\n"
            . "BY ASSET:\n" . json_encode($byAsset->toArray(), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "AUDITS THIS WEEK:\n" . json_encode($recentAudits->toArray(), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "CURRENT PARAMS:\n" . json_encode($strategyParams->toArray(), JSON_UNESCAPED_SLASHES) . "\n\n"
            . "Respond in JSON ONLY:\n"
            . '{"analysis": "3-5 sentences covering trends and patterns", "suggested_param_changes": [{"param_key": "KEY", "current_value": "X", "suggested_value": "Y", "reason": "why", "action": "review_required"}], "risk_assessment": "string", "forward_recommendations": "2-3 sentences", "overall_assessment": "excellent|good|acceptable|concerning|poor"}';

        if ($maxPromptTokens !== null) {
            $user = $this->trimToTokenBudget($user, $maxPromptTokens);
        }

        return ['system' => $system, 'user' => $user];
    }

    private function estimateTokens(string $content): int
    {
        // Rough heuristic: ~4 chars/token for English mixed JSON text.
        return (int) ceil(strlen($content) / 4);
    }

    private function trimToTokenBudget(string $content, int $maxPromptTokens): string
    {
        if ($maxPromptTokens <= 0) {
            return $content;
        }

        if ($this->estimateTokens($content) <= $maxPromptTokens) {
            return $content;
        }

        $maxChars = max(1000, $maxPromptTokens * 4);
        return Str::limit($content, $maxChars, '');
    }
}
