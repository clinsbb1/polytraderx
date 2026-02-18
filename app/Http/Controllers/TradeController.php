<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TradeController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->applyFilters(Trade::forUser(auth()->id()), $request);

        $trades = $query->latest('entry_at')->paginate(25)->withQueryString();

        return view('trades.index', compact('trades'));
    }

    public function show(Trade $trade): View
    {
        abort_if((int) $trade->user_id !== auth()->id(), 403);

        if ($this->canLoadTradeLogs()) {
            try {
                $trade->load('tradeLogs');
                $trade->setRelation('tradeLogs', $trade->tradeLogs->sortBy('created_at')->values());
            } catch (\Throwable) {
                $trade->setRelation('tradeLogs', new EloquentCollection());
            }
        } else {
            $trade->setRelation('tradeLogs', new EloquentCollection());
        }

        if ($this->canLoadAiDecisions()) {
            try {
                $trade->load('aiDecisions');
            } catch (\Throwable) {
                $trade->setRelation('aiDecisions', new EloquentCollection());
            }
        } else {
            $trade->setRelation('aiDecisions', new EloquentCollection());
        }

        $audit = null;
        if ($trade->status === 'lost' && $trade->audited) {
            $audit = $this->findTradeAudit((int) auth()->id(), (int) $trade->id);
        }

        return view('trades.show', compact('trade', 'audit'));
    }

    public function export(Request $request): StreamedResponse
    {
        // Check if user has CSV export permission
        $subscriptionService = app(SubscriptionService::class);
        if (!$subscriptionService->canExportCsv(auth()->user())) {
            return back()->with('error', 'CSV export is available on Pro and Advanced plans.');
        }

        $query = $this->applyFilters(Trade::forUser(auth()->id()), $request);
        $trades = $query->latest('entry_at')->get();

        $filename = 'polytraderx-trades-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($trades): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'date', 'asset', 'market', 'side', 'amount',
                'entry_price', 'exit_price', 'pnl', 'status',
                'confidence', 'tier',
            ]);

            foreach ($trades as $trade) {
                fputcsv($handle, [
                    $trade->entry_at?->format('Y-m-d H:i:s') ?? '',
                    $trade->asset,
                    $trade->market_question,
                    $trade->side,
                    $trade->amount,
                    $trade->entry_price,
                    $trade->exit_price,
                    $trade->pnl,
                    $trade->status,
                    $trade->confidence_score,
                    $trade->decision_tier,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($asset = $request->get('asset')) {
            $query->forAsset($asset);
        }

        if ($side = $request->get('side')) {
            $query->where('side', $side);
        }

        if ($from = $request->get('from')) {
            $query->where('entry_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $request->get('to')) {
            $query->where('entry_at', '<=', $to . ' 23:59:59');
        }

        return $query;
    }

    private function findTradeAudit(int $userId, int $tradeId): ?AiAudit
    {
        if (! $this->canLoadAiAudits()) {
            return null;
        }

        try {
            return AiAudit::forUser($userId)
                ->whereJsonContains('losing_trade_ids', $tradeId)
                ->latest('created_at')
                ->first();
        } catch (\Throwable) {
            try {
                // Fallback for legacy rows where losing_trade_ids may contain invalid JSON.
                return AiAudit::forUser($userId)
                    ->latest('created_at')
                    ->get()
                    ->first(function (AiAudit $audit) use ($tradeId) {
                        $ids = $audit->losing_trade_ids;
                        if (!is_array($ids)) {
                            return false;
                        }

                        return in_array($tradeId, array_map('intval', $ids), true);
                    });
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function canLoadTradeLogs(): bool
    {
        try {
            return Schema::hasTable('trade_logs')
                && Schema::hasColumn('trade_logs', 'trade_id');
        } catch (\Throwable) {
            return false;
        }
    }

    private function canLoadAiDecisions(): bool
    {
        try {
            return Schema::hasTable('ai_decisions')
                && Schema::hasColumn('ai_decisions', 'trade_id');
        } catch (\Throwable) {
            return false;
        }
    }

    private function canLoadAiAudits(): bool
    {
        try {
            return Schema::hasTable('ai_audits')
                && Schema::hasColumn('ai_audits', 'user_id')
                && Schema::hasColumn('ai_audits', 'losing_trade_ids');
        } catch (\Throwable) {
            return false;
        }
    }
}
