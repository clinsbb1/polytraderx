<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Trade;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_trades' => Trade::count(),
            'total_revenue' => (float) Payment::where('status', 'finished')->sum('amount_usd'),
            'users_today' => User::whereDate('created_at', today())->count(),
            'trades_today' => Trade::whereDate('created_at', today())->count(),
            'active_bots' => User::where('is_active', true)
                ->whereNotNull('last_bot_heartbeat')
                ->where('last_bot_heartbeat', '>', now()->subMinutes(5))
                ->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'paid_subscriptions' => User::whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>', now())
                ->where('subscription_plan', '!=', 'free_trial')
                ->count(),
        ];

        $recentUsers = User::latest()->take(10)->get();
        $recentPayments = Payment::with('user', 'subscriptionPlan')->latest()->take(10)->get();
        $recentTrades = Trade::with('user')->latest()->take(10)->get();

        $signupsPerDay = User::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenuePerDay = Payment::where('status', 'finished')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount_usd) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $tradesPerDay = Trade::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentPayments',
            'recentTrades',
            'signupsPerDay',
            'revenuePerDay',
            'tradesPerDay',
        ));
    }
}
