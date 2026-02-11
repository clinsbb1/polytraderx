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
            'total_revenue' => Payment::where('status', 'finished')->sum('amount_usd'),
            'users_today' => User::whereDate('created_at', today())->count(),
            'trades_today' => Trade::whereDate('created_at', today())->count(),
            'active_bots' => User::where('is_active', true)
                ->where('onboarding_completed', true)
                ->whereNotNull('last_bot_heartbeat')
                ->where('last_bot_heartbeat', '>', now()->subMinutes(10))
                ->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentPayments = Payment::with('user', 'subscriptionPlan')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentPayments'));
    }
}
