<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StrategyController extends Controller
{
    public function index(SettingsService $settings): View
    {
        $userId = auth()->id();
        $telegramLinked = auth()->user()?->hasTelegramLinked() ?? false;
        $groups = [
            'risk' => $settings->getGroup('risk', $userId),
            'trading' => $settings->getGroup('trading', $userId)->reject(fn($param) => $param->key === 'DRY_RUN'),
            'notifications' => $settings->getGroup('notifications', $userId),
        ];

        return view('strategy.index', compact('groups', 'telegramLinked'));
    }

    public function update(Request $request, string $group, SettingsService $settings): RedirectResponse
    {
        $params = $request->input('params', []);
        $simulatorEnabled = false;
        $blockedSimulatorEnable = false;
        $telegramLinked = $request->user()?->hasTelegramLinked() ?? false;

        foreach ($params as $key => $value) {
            if ($key === 'SIMULATOR_ENABLED') {
                $wantsEnable = in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);

                if ($wantsEnable && !$telegramLinked) {
                    $blockedSimulatorEnable = true;
                    $settings->set('SIMULATOR_ENABLED', 'false', 'system', auth()->id());
                    continue;
                }

                $simulatorEnabled = $wantsEnable;
            }

            $settings->set($key, $value, 'admin', auth()->id());
        }

        if ($simulatorEnabled) {
            session()->flash('analytics_events', [
                ['name' => 'simulation_run'],
            ]);
        }

        $message = 'Strategy parameters updated.';
        if ($blockedSimulatorEnable) {
            $message .= ' Simulator remains off until Telegram is linked.';
        }

        return redirect()->route('strategy.index')->with('success', $message);
    }

    public function toggleSimulator(Request $request, SettingsService $settings): RedirectResponse
    {
        $enabled = $request->boolean('simulator_enabled');
        $telegramLinked = $request->user()?->hasTelegramLinked() ?? false;

        if ($enabled && !$telegramLinked) {
            $settings->set('SIMULATOR_ENABLED', 'false', 'system', auth()->id());

            return back()->with('toast', 'Link Telegram first to turn on simulator. Simulator remains off.');
        }

        $settings->set('SIMULATOR_ENABLED', $enabled ? 'true' : 'false', 'admin', auth()->id());

        if ($enabled) {
            session()->flash('analytics_events', [
                ['name' => 'simulation_run'],
            ]);
        }

        return back()->with('toast', $enabled ? 'Simulator turned on.' : 'Simulator turned off.');
    }
}
