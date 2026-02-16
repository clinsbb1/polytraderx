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
        PlatformSetting::firstOrCreate(
            ['key' => 'TELEGRAM_WEBHOOK_SECRET'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'telegram',
                'description' => 'Secret token used to verify Telegram webhook requests',
            ]
        );

        $settings = PlatformSetting::orderBy('group')->orderBy('key')->get();
        $groups = $settings->groupBy('group');

        return view('admin.settings.index', compact('groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $submitted = $request->input('settings', []);
        $allSettings = PlatformSetting::query()->select('key', 'type')->get();

        foreach ($allSettings as $setting) {
            if ($setting->type === 'boolean') {
                $value = array_key_exists($setting->key, $submitted) ? $submitted[$setting->key] : 'false';
                $this->platformSettings->set($setting->key, $value);
                continue;
            }

            if (array_key_exists($setting->key, $submitted)) {
                $this->platformSettings->set($setting->key, $submitted[$setting->key]);
            }
        }

        return back()->with('success', 'Platform settings updated.');
    }
}
