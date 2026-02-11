<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\View\View;

class TradeController extends Controller
{
    public function index(): View
    {
        return view('trades.index');
    }

    public function show(Trade $trade): View
    {
        return view('trades.show', compact('trade'));
    }
}
