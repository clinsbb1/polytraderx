<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiDecision;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAiCostController extends Controller
{
    public function index(Request $request): View
    {
        $totalSpend = AiDecision::sum('cost_usd');
        $todaySpend = AiDecision::whereDate('created_at', today())->sum('cost_usd');
        $monthSpend = AiDecision::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('cost_usd');

        $perUserCosts = AiDecision::selectRaw('user_id, SUM(cost_usd) as total_cost, COUNT(*) as total_calls')
            ->groupBy('user_id')
            ->orderByDesc('total_cost')
            ->take(50)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->user_id);
                return [
                    'user' => $user,
                    'total_cost' => $row->total_cost,
                    'total_calls' => $row->total_calls,
                ];
            });

        $dailyCosts = AiDecision::selectRaw('DATE(created_at) as date, SUM(cost_usd) as daily_cost, COUNT(*) as daily_calls')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return view('admin.ai-costs.index', compact(
            'totalSpend',
            'todaySpend',
            'monthSpend',
            'perUserCosts',
            'dailyCosts',
        ));
    }
}
