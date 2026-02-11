<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TradeLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $query = TradeLog::whereHas('trade', fn ($q) => $q->where('user_id', auth()->id()));

        if ($level = $request->get('level')) {
            $query->where('level', $level);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        return view('logs.index', compact('logs'));
    }
}
