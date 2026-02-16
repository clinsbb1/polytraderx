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
        $groups = [
            'risk' => $settings->getGroup('risk', $userId),
            'trading' => $settings->getGroup('trading', $userId)->reject(fn($param) => $param->key === 'DRY_RUN'),
            'notifications' => $settings->getGroup('notifications', $userId),
        ];

        return view('strategy.index', compact('groups'));
    }

    public function update(Request $request, string $group, SettingsService $settings): RedirectResponse
    {
        $params = $request->input('params', []);
        $simulatorEnabled = false;

        foreach ($params as $key => $value) {
            $settings->set($key, $value, 'admin', auth()->id());

            if ($key === 'SIMULATOR_ENABLED') {
                $simulatorEnabled = in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
            }
        }

        if ($simulatorEnabled) {
            session()->flash('analytics_events', [
                ['name' => 'simulation_run'],
            ]);
        }

        return redirect()->route('strategy.index')
            ->with('success', 'Strategy parameters updated.');
    }
}
