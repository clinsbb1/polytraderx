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
        $query = TradeLog::forUser(auth()->id());

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trade_id', 'LIKE', '%' . $search . '%')
                  ->orWhere('data', 'LIKE', '%' . $search . '%');
            });
        }

        if ($event = $request->get('event')) {
            $query->where('event', $event);
        }

        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->latest('created_at')->paginate(50)->withQueryString();

        return view('logs.index', compact('logs'));
    }
}
