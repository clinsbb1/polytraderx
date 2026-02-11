<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function edit(): View
    {
        $userId = auth()->id();
        $notifications = $this->settings->getGroup('notifications', $userId);

        return view('settings.notifications', compact('notifications'));
    }

    public function update(Request $request): RedirectResponse
    {
        $userId = auth()->id();
        $params = $request->input('params', []);

        foreach ($params as $key => $value) {
            $this->settings->set($key, $value, 'user', $userId);
        }

        return back()->with('success', 'Notification settings updated.');
    }
}
