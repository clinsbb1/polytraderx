<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BalanceSnapshot;
use Illuminate\View\View;

class BalanceController extends Controller
{
    public function index(): View
    {
        $snapshots = BalanceSnapshot::forUser(auth()->id())
            ->orderBy('snapshot_at', 'desc')
            ->take(100)
            ->get();

        return view('balance.index', compact('snapshots'));
    }
}
