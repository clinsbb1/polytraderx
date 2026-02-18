<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
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
        if ((int) $trade->user_id !== (int) auth()->id()) {
            abort(403);
        }

        return view('trades.show', ['trade' => $trade]);
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

}
