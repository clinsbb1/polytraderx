<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BalanceSnapshot;
use App\Models\DailySummary;
use App\Models\Trade;
use Illuminate\View\View;

class BalanceController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();
        $openTradeCount = Trade::forUser($userId)
            ->whereIn('status', ['open', 'pending'])
            ->count();

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
            'openTradeCount',
        ));
    }

    public function reset()
    {
        $userId = auth()->id();
        $openTradeCount = Trade::forUser($userId)
            ->whereIn('status', ['open', 'pending'])
            ->count();

        if ($openTradeCount > 0) {
            return redirect()->route('balance.index')->with(
                'toast',
                "Balance reset is unavailable while you have {$openTradeCount} open/pending trade(s). "
                . 'Please turn off the simulator and wait for those trades to resolve first.'
            );
        }

        // Validate and get the reset amount
        $amount = (float) request()->input('amount', 100);

        // Validate amount range
        if ($amount < 1 || $amount > 1000000) {
            return redirect()->route('balance.index')->with('toast', 'Invalid amount. Must be between $1 and $1,000,000.');
        }

        // Delete all balance snapshots for this user
        BalanceSnapshot::forUser($userId)->delete();

        // Delete all daily summaries for this user
        DailySummary::forUser($userId)->delete();

        // Create initial snapshot with specified balance
        BalanceSnapshot::create([
            'user_id' => $userId,
            'balance_usdc' => $amount,
            'open_positions_value' => 0.00,
            'total_equity' => $amount,
            'snapshot_at' => now(),
        ]);

        return redirect()->route('balance.index')->with('toast', 'Balance reset to $' . number_format($amount, 2) . '. All equity history cleared.');
    }
}
