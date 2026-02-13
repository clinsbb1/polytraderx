<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiDecision;
use Illuminate\View\View;

class AiCostController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $totalCost = (float) AiDecision::forUser($userId)->sum('cost_usd');
        $monthlyCost = (float) AiDecision::forUser($userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('cost_usd');
        $todayCost = (float) AiDecision::forUser($userId)
            ->whereDate('created_at', today())
            ->sum('cost_usd');

        // Tier breakdown for doughnut chart
        $tierBreakdown = AiDecision::forUser($userId)
            ->selectRaw("tier, SUM(cost_usd) as total_cost, COUNT(*) as call_count")
            ->groupBy('tier')
            ->get();

        // Daily spend for bar chart (last 30 days)
        $dailySpend = AiDecision::forUser($userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, SUM(cost_usd) as daily_cost")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Projected monthly cost (based on daily average this month)
        $daysThisMonth = (int) now()->day;
        $daysInMonth = (int) now()->daysInMonth;
        $projectedCost = $daysThisMonth > 0
            ? ($monthlyCost / $daysThisMonth) * $daysInMonth
            : 0.0;

        // Recent decisions table
        $decisions = AiDecision::forUser($userId)
            ->latest()
            ->paginate(30);

        return view('ai-costs.index', compact(
            'totalCost',
            'monthlyCost',
            'todayCost',
            'tierBreakdown',
            'dailySpend',
            'projectedCost',
            'decisions',
        ));
    }
}
