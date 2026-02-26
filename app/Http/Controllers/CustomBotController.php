<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomBotRequest;
use App\Services\Email\LifecycleEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomBotController extends Controller
{
    public function show(): View
    {
        // Active = in-progress or accepted request (blocks new submission)
        $activeRequest = CustomBotRequest::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'reviewing', 'accepted'])
            ->latest()
            ->first();

        // Show a declined notice only when there's no active request
        $declinedRequest = $activeRequest === null
            ? CustomBotRequest::where('user_id', auth()->id())
                ->where('status', 'declined')
                ->latest()
                ->first()
            : null;

        return view('custom-bot.show', compact('activeRequest', 'declinedRequest'));
    }

    public function store(Request $request, LifecycleEmailService $emailService): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'contact'          => ['nullable', 'string', 'max:100'],
            'strategy_summary' => ['required', 'string', 'max:2000'],
            'markets'          => ['nullable', 'string', 'max:255'],
            'timeframe'        => ['nullable', 'string', 'max:50'],
            'max_bet'          => ['nullable', 'numeric', 'min:0'],
            'daily_loss'       => ['nullable', 'numeric', 'min:0'],
            'wants_ai'         => ['required', 'in:yes,no'],
            'budget_range'     => ['required', 'string', 'max:50'],
            'timeline'         => ['required', 'string', 'max:50'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'disclaimer'       => ['accepted'],
        ]);

        $botRequest = CustomBotRequest::create([
            'user_id'          => auth()->id(),
            'name'             => $validated['name'],
            'email'            => auth()->user()->email,
            'contact'          => $validated['contact'] ?? null,
            'strategy_summary' => $validated['strategy_summary'],
            'markets'          => $validated['markets'] ?? null,
            'timeframe'        => $validated['timeframe'] ?? null,
            'risk_limits_json' => [
                'max_bet'    => $validated['max_bet'] ?? null,
                'daily_loss' => $validated['daily_loss'] ?? null,
            ],
            'wants_ai'     => $validated['wants_ai'] === 'yes',
            'budget_range' => $validated['budget_range'],
            'timeline'     => $validated['timeline'],
            'notes'        => $validated['notes'] ?? null,
            'status'       => 'pending',
        ]);

        $emailService->sendCustomBotRequestNotification($botRequest);

        return redirect()->route('custom-bot.show')
            ->with('success', 'Your request has been submitted. We will review it and get back to you via email.');
    }
}
