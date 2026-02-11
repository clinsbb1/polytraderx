<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiDecision;
use Illuminate\View\View;

class AiCostController extends Controller
{
    public function index(): View
    {
        $decisions = AiDecision::forUser(auth()->id())
            ->latest()
            ->paginate(50);

        $totalCost = AiDecision::forUser(auth()->id())->sum('cost_usd');
        $monthlyCost = AiDecision::forUser(auth()->id())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('cost_usd');

        return view('ai-costs.index', compact('decisions', 'totalCost', 'monthlyCost'));
    }
}
