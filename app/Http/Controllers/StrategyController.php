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
        $groups = [
            'risk' => $settings->getGroup('risk'),
            'trading' => $settings->getGroup('trading'),
            'ai' => $settings->getGroup('ai'),
            'notifications' => $settings->getGroup('notifications'),
        ];

        return view('strategy.index', compact('groups'));
    }

    public function update(Request $request, string $group, SettingsService $settings): RedirectResponse
    {
        return redirect()->route('strategy.index')
            ->with('success', 'Strategy parameters updated.');
    }
}
