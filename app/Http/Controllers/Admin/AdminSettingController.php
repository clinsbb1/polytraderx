<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    public function __construct(private PlatformSettingsService $platformSettings) {}

    public function index(): View
    {
        $settings = PlatformSetting::orderBy('group')->orderBy('key')->get();
        $groups = $settings->groupBy('group');

        return view('admin.settings.index', compact('groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            $this->platformSettings->set($key, $value);
        }

        return back()->with('success', 'Platform settings updated.');
    }
}
