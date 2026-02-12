<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AiAudit;
use App\Models\AiDecision;
use App\Models\Trade;
use App\Models\TradeLog;

class ForensicsBuilder
{
    public function buildForensics(Trade $trade): array
    {
        $tradeLogs = TradeLog::where('trade_id', $trade->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($log) => [
                'event' => $log->event,
                'data' => $log->data,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->toArray();

        $aiDecision = AiDecision::where('trade_id', $trade->id)
            ->first();

        $concurrentPositions = Trade::forUser($trade->user_id)
            ->where('id', '!=', $trade->id)
            ->where('entry_at', '<=', $trade->entry_at)
            ->where(function ($q) use ($trade) {
                $q->whereNull('resolved_at')
                    ->orWhere('resolved_at', '>=', $trade->entry_at);
            })
            ->get()
            ->map(fn($t) => [
                'asset' => $t->asset,
                'side' => $t->side,
                'status' => $t->status,
                'pnl' => $t->pnl,
            ])
            ->toArray();

        $recentPattern = Trade::forUser($trade->user_id)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', $trade->resolved_at ?? now())
            ->latest('resolved_at')
            ->take(20)
            ->pluck('status')
            ->toArray();

        $similarAudits = AiAudit::forUser($trade->user_id)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($a) => [
                'trigger' => $a->trigger,
                'analysis' => $a->analysis,
                'suggested_fixes' => $a->suggested_fixes,
            ])
            ->toArray();

        // Reconstruct external data from trade logs
        $placedLog = collect($tradeLogs)->firstWhere('event', 'placed');
        $externalData = $placedLog['data']['external_data'] ?? [
            'spot_price' => $trade->external_spot_at_entry,
            'spot_at_resolution' => $trade->external_spot_at_resolution,
        ];

        return [
            'trade' => [
                'id' => $trade->id,
                'market_id' => $trade->market_id,
                'market_question' => $trade->market_question,
                'asset' => $trade->asset,
                'side' => $trade->side,
                'entry_price' => (float) $trade->entry_price,
                'exit_price' => (float) $trade->exit_price,
                'amount' => (float) $trade->amount,
                'pnl' => (float) $trade->pnl,
                'confidence_score' => (float) $trade->confidence_score,
                'decision_tier' => $trade->decision_tier,
                'decision_reasoning' => $trade->decision_reasoning,
                'external_spot_at_entry' => (float) $trade->external_spot_at_entry,
                'external_spot_at_resolution' => (float) $trade->external_spot_at_resolution,
                'entry_at' => $trade->entry_at?->toIso8601String(),
                'resolved_at' => $trade->resolved_at?->toIso8601String(),
            ],
            'trade_logs' => $tradeLogs,
            'ai_decision' => $aiDecision ? [
                'tier' => $aiDecision->tier,
                'model' => $aiDecision->model_used,
                'prompt' => $aiDecision->prompt,
                'response' => $aiDecision->response,
            ] : null,
            'market_snapshot' => [
                'market_id' => $trade->market_id,
                'question' => $trade->market_question,
                'end_time' => $trade->market_end_time?->toIso8601String(),
            ],
            'external_data' => $externalData,
            'context' => [
                'concurrent_positions' => $concurrentPositions,
                'recent_pattern' => $recentPattern,
            ],
            'similar_losses' => $similarAudits,
        ];
    }
}
