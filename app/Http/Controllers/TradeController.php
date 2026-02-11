<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TradeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Trade::forUser(auth()->id());

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($asset = $request->get('asset')) {
            $query->forAsset($asset);
        }

        $trades = $query->latest()->paginate(20)->withQueryString();

        return view('trades.index', compact('trades'));
    }

    public function show(Trade $trade): View
    {
        abort_if((int) $trade->user_id !== auth()->id(), 403);

        $trade->load('tradeLogs', 'aiDecisions');

        return view('trades.show', compact('trade'));
    }
}
