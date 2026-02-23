<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAudit;
use App\Models\Announcement;
use App\Models\AnnouncementDismissal;
use App\Models\BalanceSnapshot;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Support\AnnouncementTemplate;
use App\Services\Analytics\StrategyMetrics;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SettingsService $settings, StrategyMetrics $metrics): View
    {
        $user = auth()->user();
        $userId = $user->id;

        // Calculate strategy health metrics
        $drawdown = $metrics->calculateMaxDrawdown($user, 30);
        $stability = $metrics->assessStability($user, 30);

        $todayPnl = (float) Trade::forUser($userId)->whereDate('resolved_at', today())->sum('pnl');
        $todayTradeCount = Trade::forUser($userId)->whereDate('created_at', today())->count();
        $openPositions = Trade::forUser($userId)->where('status', 'open')->get();
        $latestBalance = BalanceSnapshot::forUser($userId)->latest('snapshot_at')->first();

        $winRate7d = $this->calculateWinRate($userId, 7);
        $winRate30d = $this->calculateWinRate($userId, 30);
        $winRateToday = $this->calculateWinRate($userId, 0);

        $recentTrades = Trade::forUser($userId)->latest('created_at')->take(10)->get();
        $announcements = Announcement::forDashboard($user)
            ->latest()
            ->take(3)
            ->get()
            ->map(function (Announcement $announcement) use ($user) {
                $announcement->rendered_title = AnnouncementTemplate::render((string) $announcement->title, $user);
                $announcement->rendered_body = AnnouncementTemplate::render((string) $announcement->body, $user);

                return $announcement;
            });
        $pendingAudits = AiAudit::forUser($userId)->where('status', 'pending_review')->count();

        $dryRun = $settings->getBool('DRY_RUN', true, $userId);
        $simulatorEnabled = $settings->getBool('SIMULATOR_ENABLED', false, $userId);

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
        $strategyHealth = match ($stability) {
            'excellent' => 'Excellent',
            'very_good' => 'Very Good',
            'good' => 'Good',
            'fair' => 'Fair',
            'needs_improvement' => 'Needs Improvement',
            'building_history' => 'Building History',
            default => Str::of($stability)->replace('_', ' ')->title()->value(),
        };
        $maxDrawdown = $drawdown['percent'];

        // Additional stats
        $totalTrades = Trade::forUser($userId)->whereIn('status', ['won', 'lost'])->count();
        $totalWins = Trade::forUser($userId)->where('status', 'won')->count();
        $totalLosses = Trade::forUser($userId)->where('status', 'lost')->count();

        // Best day in last 7 days
        $bestDayData = DailySummary::forUser($userId)
            ->where('date', '>=', now()->subDays(7))
            ->orderBy('net_pnl', 'desc')
            ->first();
        $bestDay7d = $bestDayData ? (float) $bestDayData->net_pnl : 0;
        $bestDayDate = $bestDayData ? $bestDayData->date->format('M j') : 'No data';

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
            'simulatorEnabled',
            'equityCurve',
            'dailyPnl',
            'telegramLinked',
            'strategyHealth',
            'maxDrawdown',
            'totalTrades',
            'totalWins',
            'totalLosses',
            'bestDay7d',
            'bestDayDate'
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

    public function dismissAnnouncement(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $isVisibleToUser = Announcement::forDashboard($user)
            ->whereKey($announcement->id)
            ->exists();

        if (! $isVisibleToUser) {
            abort(404);
        }

        AnnouncementDismissal::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $userId,
            ],
            [
                'dismissed_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }
}
