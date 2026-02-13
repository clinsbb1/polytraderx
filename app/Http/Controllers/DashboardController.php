<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAudit;
use App\Models\Announcement;
use App\Models\BalanceSnapshot;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Services\Settings\SettingsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SettingsService $settings): View
    {
        $user = auth()->user();
        $userId = $user->id;

        $todayPnl = (float) Trade::forUser($userId)->whereDate('resolved_at', today())->sum('pnl');
        $todayTradeCount = Trade::forUser($userId)->whereDate('created_at', today())->count();
        $openPositions = Trade::forUser($userId)->where('status', 'open')->get();
        $latestBalance = BalanceSnapshot::forUser($userId)->latest('snapshot_at')->first();

        $winRate7d = $this->calculateWinRate($userId, 7);
        $winRate30d = $this->calculateWinRate($userId, 30);
        $winRateToday = $this->calculateWinRate($userId, 0);

        $recentTrades = Trade::forUser($userId)->latest('created_at')->take(10)->get();
        $announcements = Announcement::forDashboard()->latest()->take(3)->get();
        $pendingAudits = AiAudit::forUser($userId)->where('status', 'pending_review')->count();

        $dryRun = $settings->getBool('DRY_RUN', true, $userId);
        $botEnabled = $settings->getBool('BOT_ENABLED', false, $userId);

        $equityCurve = BalanceSnapshot::forUser($userId)
            ->where('snapshot_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(snapshot_at) as date, MAX(total_equity) as equity')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyPnl = DailySummary::forUser($userId)
            ->where('date', '>=', now()->subDays(14))
            ->orderBy('date')
            ->get();

        $telegramLinked = $user->hasTelegramLinked();
        $credentialsConfigured = $user->hasPolymarketConfigured();

        return view('dashboard', compact(
            'user',
            'todayPnl',
            'todayTradeCount',
            'openPositions',
            'latestBalance',
            'winRateToday',
            'winRate7d',
            'winRate30d',
            'recentTrades',
            'announcements',
            'pendingAudits',
            'dryRun',
            'botEnabled',
            'equityCurve',
            'dailyPnl',
            'telegramLinked',
            'credentialsConfigured',
        ));
    }

    private function calculateWinRate(int $userId, int $days): float
    {
        $query = Trade::forUser($userId)->whereIn('status', ['won', 'lost']);

        if ($days > 0) {
            $query->where('resolved_at', '>=', now()->subDays($days));
        } else {
            $query->whereDate('resolved_at', today());
        }

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $won = (clone $query)->where('status', 'won')->count();

        return round(($won / $total) * 100, 1);
    }
}
