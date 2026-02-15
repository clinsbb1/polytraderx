<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulationAcknowledgmentController extends Controller
{
    /**
     * Show the simulation acknowledgment page.
     */
    public function show(): View
    {
        return view('simulation.acknowledge');
    }

    /**
     * Handle acceptance of simulation terms.
     */
    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'acknowledge' => ['required', 'accepted'],
        ]);

        $user = auth()->user();
        $user->simulation_acknowledged_at = now();
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', 'Thank you for acknowledging. Welcome to PolyTraderX!');
    }
}
