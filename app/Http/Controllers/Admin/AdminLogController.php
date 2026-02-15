<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeLog;
use App\Models\User;
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

        if ($event = $request->get('event')) {
            $query->where('event', $event);
        }

        if ($level = $request->get('level')) {
            // Trade logs don't have a dedicated level column; use event as a fallback classifier.
            $query->where('event', $level);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();
        $users = User::query()->select('id', 'name', 'account_id')->orderBy('name')->get();

        return view('admin.logs.index', compact('logs', 'users'));
    }
}
