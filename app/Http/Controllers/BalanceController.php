<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BalanceSnapshot;
use App\Models\DailySummary;
use Illuminate\View\View;

class BalanceController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        // Latest snapshot for stat cards
        $latestSnapshot = BalanceSnapshot::forUser($userId)
            ->orderBy('snapshot_at', 'desc')
            ->first();

        // Equity curve data (30 days, one per day)
        $equityCurve = BalanceSnapshot::forUser($userId)
            ->where('snapshot_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(snapshot_at) as date, MAX(total_equity) as equity')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily PnL from DailySummary (30 days)
        $dailyPnl = DailySummary::forUser($userId)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date')
            ->get();

        // Last 7 days stats from DailySummary
        $weeklyStats = DailySummary::forUser($userId)
            ->where('date', '>=', now()->subDays(7))
            ->orderBy('date', 'desc')
            ->get();

        // Calculate cumulative PnL for the weekly stats table
        $cumulativePnl = '0.00';
        $weeklyStatsWithCumulative = $weeklyStats->sortBy('date')->map(function ($day) use (&$cumulativePnl) {
            $cumulativePnl = bcadd($cumulativePnl, (string) ($day->net_pnl ?? '0'), 2);
            $day->cumulative_pnl = $cumulativePnl;
            return $day;
        })->sortByDesc('date')->values();

        return view('balance.index', compact(
            'latestSnapshot',
            'equityCurve',
            'dailyPnl',
            'weeklyStatsWithCumulative',
        ));
    }
}
