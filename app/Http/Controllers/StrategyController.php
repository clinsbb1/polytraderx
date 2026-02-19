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
            'trading' => $settings->getGroup('trading', $userId)
                ->reject(fn($param) => in_array($param->key, ['DRY_RUN', 'ENTRY_WINDOW_SECONDS'], true)),
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
        $pendingWindowMin = null;
        $pendingWindowMax = null;

        foreach ($params as $key => $value) {
            if ($key === 'ENTRY_WINDOW_MIN_SECONDS') {
                $pendingWindowMin = (int) $value;
                continue;
            }

            if ($key === 'ENTRY_WINDOW_MAX_SECONDS') {
                $pendingWindowMax = (int) $value;
                continue;
            }

            // Backward compatibility for older clients still submitting one value.
            if ($key === 'ENTRY_WINDOW_SECONDS') {
                $pendingWindowMin = 5;
                $pendingWindowMax = (int) $value;
                continue;
            }

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

        if ($pendingWindowMin !== null || $pendingWindowMax !== null) {
            $currentMin = $settings->getInt('ENTRY_WINDOW_MIN_SECONDS', 5, auth()->id());
            $legacyMax = $settings->getInt('ENTRY_WINDOW_SECONDS', 60, auth()->id());
            $currentMax = $settings->getInt('ENTRY_WINDOW_MAX_SECONDS', $legacyMax, auth()->id());

            $windowMin = $pendingWindowMin ?? $currentMin;
            $windowMax = $pendingWindowMax ?? $currentMax;
            [$windowMin, $windowMax] = $this->normalizeEntryWindowRange($windowMin, $windowMax);

            $settings->set('ENTRY_WINDOW_MIN_SECONDS', (string) $windowMin, 'admin', auth()->id());
            $settings->set('ENTRY_WINDOW_MAX_SECONDS', (string) $windowMax, 'admin', auth()->id());

            // Keep legacy key aligned as max-bound fallback.
            $settings->set('ENTRY_WINDOW_SECONDS', (string) $windowMax, 'admin', auth()->id());
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

    private function normalizeEntryWindowRange(int $minSeconds, int $maxSeconds): array
    {
        $minSeconds = max(5, min(900, $minSeconds));
        $maxSeconds = max(5, min(900, $maxSeconds));

        if ($minSeconds > $maxSeconds) {
            [$minSeconds, $maxSeconds] = [$maxSeconds, $minSeconds];
        }

        return [$minSeconds, $maxSeconds];
    }
}
