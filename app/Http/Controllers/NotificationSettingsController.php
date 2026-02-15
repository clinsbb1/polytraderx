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
        $inputParams = $request->input('params', []);
        $notificationParams = $this->settings->getGroup('notifications', $userId)->keyBy('key');

        foreach ($notificationParams as $key => $param) {
            // Unchecked checkboxes are omitted from POST payload, so force false.
            if ($param->type === 'boolean') {
                $value = array_key_exists($key, $inputParams) ? 'true' : 'false';
                $this->settings->set($key, $value, 'system', $userId);
                continue;
            }

            if (array_key_exists($key, $inputParams)) {
                $this->settings->set($key, (string) $inputParams[$key], 'system', $userId);
            }
        }

        return back()->with('success', 'Notification settings updated.');
    }
}
