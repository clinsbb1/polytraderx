<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use App\Services\Trading\SimulationBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class StrategyController extends Controller
{
    public function index(SettingsService $settings): View
    {
        $userId = auth()->id();
        $telegramLinked = auth()->user()?->hasTelegramLinked() ?? false;
        $hasActiveSub = auth()->user()?->isSubscriptionActive() ?? false;
        $groups = [
            'risk' => $settings->getGroup('risk', $userId),
            'trading' => $settings->getGroup('trading', $userId)
                ->reject(fn($param) => in_array($param->key, ['DRY_RUN', 'ENTRY_WINDOW_SECONDS'], true)),
            'notifications' => $settings->getGroup('notifications', $userId),
        ];

        return view('strategy.index', compact('groups', 'telegramLinked', 'hasActiveSub'));
    }

    public function update(
        Request $request,
        string $group,
        SettingsService $settings,
        SimulationBalanceService $balanceService
    ): RedirectResponse
    {
        $params = $request->input('params', []);
        $userId = (int) $request->user()->id;
        $simulatorEnabled = false;
        $blockedSimulatorEnable = false;
        $blockedForLowBalance = false;
        $blockedNoSubscription = false;
        $telegramLinked = $request->user()?->hasTelegramLinked() ?? false;
        $hasActiveSub = $request->user()?->isSubscriptionActive() ?? false;
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

                if ($wantsEnable && !$hasActiveSub) {
                    $blockedNoSubscription = true;
                    $settings->set('SIMULATOR_ENABLED', 'false', 'system', $userId);
                    continue;
                }

                if ($wantsEnable && !$telegramLinked) {
                    $blockedSimulatorEnable = true;
                    $settings->set('SIMULATOR_ENABLED', 'false', 'system', $userId);
                    continue;
                }

                if ($wantsEnable && !$this->hasPositiveSimulatedBalance($userId, $balanceService)) {
                    $blockedForLowBalance = true;
                    $settings->set('SIMULATOR_ENABLED', 'false', 'system', $userId);
                    continue;
                }

                $simulatorEnabled = $wantsEnable;
            }

            if (in_array($key, ['MIN_CONFIDENCE_SCORE', 'MIN_ENTRY_PRICE_THRESHOLD', 'MAX_ENTRY_PRICE_THRESHOLD'], true)) {
                $value = $this->normalizeUnitIntervalInput($value);
            }

            $settings->set($key, $value, 'admin', $userId);
        }

        if ($pendingWindowMin !== null || $pendingWindowMax !== null) {
            $currentMin = $settings->getInt('ENTRY_WINDOW_MIN_SECONDS', 5, $userId);
            $legacyMax = $settings->getInt('ENTRY_WINDOW_SECONDS', 60, $userId);
            $currentMax = $settings->getInt('ENTRY_WINDOW_MAX_SECONDS', $legacyMax, $userId);

            $windowMin = $pendingWindowMin ?? $currentMin;
            $windowMax = $pendingWindowMax ?? $currentMax;
            [$windowMin, $windowMax] = $this->normalizeEntryWindowRange($windowMin, $windowMax);

            $settings->set('ENTRY_WINDOW_MIN_SECONDS', (string) $windowMin, 'admin', $userId);
            $settings->set('ENTRY_WINDOW_MAX_SECONDS', (string) $windowMax, 'admin', $userId);

            // Keep legacy key aligned as max-bound fallback.
            $settings->set('ENTRY_WINDOW_SECONDS', (string) $windowMax, 'admin', $userId);
        }

        if ($simulatorEnabled) {
            session()->flash('analytics_events', [
                ['name' => 'simulation_run'],
            ]);
        }

        $message = 'Strategy parameters updated.';
        if ($blockedNoSubscription) {
            $message .= ' Simulator requires an active paid subscription.';
        }
        if ($blockedSimulatorEnable) {
            $message .= ' Simulator remains off until Telegram is linked.';
        }
        if ($blockedForLowBalance) {
            $message .= ' Simulator remains off because your simulated balance is $0.00 or below. Reset your balance in the Balance page first.';
        }

        return redirect()->route('strategy.index')->with('success', $message);
    }

    public function toggleSimulator(
        Request $request,
        SettingsService $settings,
        SimulationBalanceService $balanceService
    ): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $enabled = $request->boolean('simulator_enabled');
        $telegramLinked = $request->user()?->hasTelegramLinked() ?? false;
        $hasActiveSub = $request->user()?->isSubscriptionActive() ?? false;

        if ($enabled && !$hasActiveSub) {
            $settings->set('SIMULATOR_ENABLED', 'false', 'system', $userId);

            return back()->with('toast', 'An active subscription is required to turn on the simulator.');
        }

        if ($enabled && !$telegramLinked) {
            $settings->set('SIMULATOR_ENABLED', 'false', 'system', $userId);

            return back()->with('toast', 'Link Telegram first to turn on simulator. Simulator remains off.');
        }

        if ($enabled && !$this->hasPositiveSimulatedBalance($userId, $balanceService)) {
            $settings->set('SIMULATOR_ENABLED', 'false', 'system', $userId);

            return back()->with('toast', 'Simulator remains off because your simulated balance is $0.00 or below. Reset your balance first.');
        }

        $settings->set('SIMULATOR_ENABLED', $enabled ? 'true' : 'false', 'admin', $userId);

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

    private function hasPositiveSimulatedBalance(int $userId, SimulationBalanceService $balanceService): bool
    {
        try {
            $state = $balanceService->calculateForUser($userId);
            $balance = (float) ($state['balance'] ?? 0.0);

            return is_finite($balance) && $balance > 0.0;
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Failed to validate simulated balance before simulator toggle', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizeUnitIntervalInput(mixed $value): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        $numeric = (float) $value;

        // Accept both ratio style (0.92) and percent style (92).
        if ($numeric > 1.0 && $numeric <= 100.0) {
            $numeric /= 100.0;
        }

        $numeric = max(0.0, min(1.0, $numeric));

        $formatted = rtrim(rtrim(number_format($numeric, 4, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
