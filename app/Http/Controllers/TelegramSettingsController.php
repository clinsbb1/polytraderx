<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        auth()->user()->update([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
        ]);

        return back()->with('success', 'Telegram account unlinked.');
    }
}
