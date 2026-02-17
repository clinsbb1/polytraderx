<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BotActivityLog;
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

    public function botActivity(Request $request): View
    {
        $query = BotActivityLog::forUser(auth()->id());

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('asset', 'LIKE', '%' . $search . '%')
                    ->orWhere('market_id', 'LIKE', '%' . $search . '%')
                    ->orWhere('action', 'LIKE', '%' . $search . '%')
                    ->orWhere('message', 'LIKE', '%' . $search . '%');
            });
        }

        if ($event = trim((string) $request->get('event', ''))) {
            $query->where('event', $event);
        }

        if ($matched = $request->get('matched')) {
            if ($matched === 'yes') {
                $query->where('matched_strategy', true);
            } elseif ($matched === 'no') {
                $query->where('matched_strategy', false);
            }
        }

        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $activities = $query->latest('created_at')->paginate(50)->withQueryString();

        return view('logs.bot-activity', compact('activities'));
    }
}
