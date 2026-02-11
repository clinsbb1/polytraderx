<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Trade;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $userId = $user->id;

        $stats = [
            'total_trades' => Trade::forUser($userId)->count(),
            'open_trades' => Trade::forUser($userId)->open()->count(),
            'won_trades' => Trade::forUser($userId)->won()->count(),
            'lost_trades' => Trade::forUser($userId)->lost()->count(),
            'today_trades' => Trade::forUser($userId)->today()->count(),
            'total_pnl' => Trade::forUser($userId)->whereNotNull('pnl')->sum('pnl'),
        ];

        $recentTrades = Trade::forUser($userId)->latest()->take(5)->get();
        $announcements = Announcement::forDashboard()->latest()->take(3)->get();

        $telegramLinked = $user->hasTelegramLinked();
        $credentialsConfigured = $user->hasPolymarketConfigured();
        $accountId = $user->account_id;

        return view('dashboard', compact(
            'stats',
            'recentTrades',
            'announcements',
            'telegramLinked',
            'credentialsConfigured',
            'accountId',
        ));
    }
}
