<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = TradeLog::with('trade.user');

        if ($userId = $request->get('user_id')) {
            $query->whereHas('trade', fn ($q) => $q->where('user_id', $userId));
        }

        if ($level = $request->get('level')) {
            $query->where('level', $level);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }
}
