<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Services\Settings\SettingsService;
use App\Services\Trading\SimulationBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TelegramSettingsController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user();

        return view('settings.telegram', compact('user'));
    }

    public function unlink(): RedirectResponse
    {
        $user = auth()->user();

        $user->update([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
        ]);

        app(SettingsService::class)->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);

        Trade::forUser($user->id)
            ->whereIn('status', ['open', 'pending'])
            ->update([
                'status' => 'cancelled',
                'resolved_at' => now(),
                'pnl' => 0,
            ]);

        try {
            app(SimulationBalanceService::class)->snapshotForUser($user->id);
        } catch (\Throwable) {
            // Snapshot failure should not block unlink flow.
        }

        return back()->with('success', 'Telegram account unlinked. Simulator turned off and active simulated trades were stopped.');
    }
}
