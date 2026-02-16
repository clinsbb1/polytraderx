<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsage;
use App\Models\Payment;
use App\Models\Trade;
use App\Models\User;
use App\Services\AI\CostTracker;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private CostTracker $costTracker) {}

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
            'paid_subscriptions' => Payment::whereIn('status', ['finished', 'confirmed'])
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->distinct('user_id')
                ->count('user_id'),
        ];

        $recentUsers = User::latest()->take(10)->get();
        $recentPayments = Payment::with('user', 'subscriptionPlan')
            ->whereIn('status', ['finished', 'confirmed'])
            ->latest()
            ->take(10)
            ->get();
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

        $currentMonth = now()->format('Y-m');
        $monthlyAiSpend = $this->costTracker->getMonthlySpend();
        $aiMonthlyBudget = app(PlatformSettingsService::class)->getFloat('AI_MONTHLY_BUDGET', 100.0);
        $aiBudgetRemaining = max(0, $aiMonthlyBudget - $monthlyAiSpend);
        $aiBudgetUsedPct = $aiMonthlyBudget > 0 ? min(100, ($monthlyAiSpend / $aiMonthlyBudget) * 100) : 0;
        $aiBudgetPaused = $aiMonthlyBudget > 0 && $monthlyAiSpend >= $aiMonthlyBudget;

        $aiTopUsers = AiUsage::query()
            ->with('user')
            ->where('month', $currentMonth)
            ->orderByDesc('total_tokens')
            ->limit(5)
            ->get();

        $health = $this->buildHealthSnapshot();

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentPayments',
            'recentTrades',
            'signupsPerDay',
            'revenuePerDay',
            'tradesPerDay',
            'monthlyAiSpend',
            'aiMonthlyBudget',
            'aiBudgetRemaining',
            'aiBudgetUsedPct',
            'aiBudgetPaused',
            'aiTopUsers',
            'health',
        ));
    }

    private function buildHealthSnapshot(): array
    {
        $services = [];

        try {
            DB::select('SELECT 1');
            $services['database'] = 'ok';
        } catch (\Throwable) {
            $services['database'] = 'error';
        }

        try {
            $response = Http::timeout(5)->get('https://api.binance.com/api/v3/ping');
            $services['binance'] = $response->successful() ? 'ok' : 'degraded';
        } catch (\Throwable) {
            $services['binance'] = 'error';
        }

        try {
            $response = Http::timeout(5)->get('https://clob.polymarket.com/time');
            $services['polymarket_public'] = $response->successful() ? 'ok' : 'degraded';
        } catch (\Throwable) {
            $services['polymarket_public'] = 'error';
        }

        $platformSettings = app(PlatformSettingsService::class);
        $telegramToken = trim((string) $platformSettings->get('TELEGRAM_BOT_TOKEN', ''));
        if ($telegramToken === '') {
            $services['telegram_bot'] = 'not_configured';
        } else {
            try {
                $telegramCheck = Http::timeout(5)->get('https://api.telegram.org/bot' . $telegramToken . '/getMe');
                $services['telegram_bot'] = $telegramCheck->successful() ? 'ok' : 'degraded';
            } catch (\Throwable) {
                $services['telegram_bot'] = 'error';
            }
        }

        $services['telegram_webhook_secret'] = $platformSettings->get('TELEGRAM_WEBHOOK_SECRET') ? 'configured' : 'not_configured';
        $anthropicKey = trim((string) $platformSettings->get('ANTHROPIC_API_KEY', ''));
        if ($anthropicKey === '') {
            $services['anthropic'] = 'not_configured';
        } else {
            try {
                $anthropic = Http::timeout(5)
                    ->withHeaders([
                        'x-api-key' => $anthropicKey,
                        'anthropic-version' => '2023-06-01',
                    ])
                    ->get('https://api.anthropic.com/v1/models');

                $services['anthropic'] = $anthropic->successful() ? 'ok' : 'degraded';
            } catch (\Throwable) {
                $services['anthropic'] = 'error';
            }
        }

        $overall = collect($services)->contains('error') ? 'degraded' : 'ok';

        return [
            'status' => $overall,
            'services' => $services,
            'checked_at' => now(),
        ];
    }
}
