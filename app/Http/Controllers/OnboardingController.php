<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserCredential;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function welcome(): View
    {
        return view('onboarding.welcome');
    }

    public function saveWelcome(Request $request): RedirectResponse
    {
        $request->validate([
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        auth()->user()->update(['timezone' => $request->timezone]);

        return redirect('/onboarding/polymarket');
    }

    public function polymarket(): View
    {
        $credential = auth()->user()->credential;

        return view('onboarding.polymarket', compact('credential'));
    }

    public function savePolymarket(Request $request): RedirectResponse
    {
        $request->validate([
            'polymarket_api_key' => ['nullable', 'string', 'max:500'],
            'polymarket_api_secret' => ['nullable', 'string', 'max:500'],
            'polymarket_passphrase' => ['nullable', 'string', 'max:500'],
        ]);

        $credential = UserCredential::firstOrCreate(['user_id' => auth()->id()]);

        $data = [];
        if ($request->filled('polymarket_api_key')) {
            $data['polymarket_api_key'] = $request->polymarket_api_key;
        }
        if ($request->filled('polymarket_api_secret')) {
            $data['polymarket_api_secret'] = $request->polymarket_api_secret;
        }
        if ($request->filled('polymarket_passphrase')) {
            $data['polymarket_passphrase'] = $request->polymarket_passphrase;
        }

        if (!empty($data)) {
            $credential->update($data);
        }

        return redirect('/onboarding/telegram');
    }

    public function telegram(): View
    {
        $credential = auth()->user()->credential;

        return view('onboarding.telegram', compact('credential'));
    }

    public function saveTelegram(Request $request): RedirectResponse
    {
        $request->validate([
            'telegram_bot_token' => ['nullable', 'string', 'max:500'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
        ]);

        $credential = UserCredential::firstOrCreate(['user_id' => auth()->id()]);

        $data = [];
        if ($request->filled('telegram_bot_token')) {
            $data['telegram_bot_token'] = $request->telegram_bot_token;
        }
        if ($request->filled('telegram_chat_id')) {
            $data['telegram_chat_id'] = $request->telegram_chat_id;
        }

        if (!empty($data)) {
            $credential->update($data);
        }

        return redirect('/onboarding/anthropic');
    }

    public function anthropic(): View
    {
        $credential = auth()->user()->credential;

        return view('onboarding.anthropic', compact('credential'));
    }

    public function saveAnthropic(Request $request): RedirectResponse
    {
        $request->validate([
            'anthropic_api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $credential = UserCredential::firstOrCreate(['user_id' => auth()->id()]);

        if ($request->filled('anthropic_api_key')) {
            $credential->update(['anthropic_api_key' => $request->anthropic_api_key]);
        }

        return redirect('/onboarding/activate');
    }

    public function activate(): View
    {
        $user = auth()->user();
        $credential = $user->credential;

        return view('onboarding.activate', compact('user', 'credential'));
    }

    public function complete(): RedirectResponse
    {
        $user = auth()->user();

        $this->settings->seedUserParams($user->id);

        $user->update(['onboarding_completed' => true]);

        return redirect('/dashboard')->with('success', 'Welcome to PolyTraderX! Your bot is ready.');
    }
}
