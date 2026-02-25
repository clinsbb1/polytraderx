<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCredential;
use App\Rules\TurnstileRule;
use App\Services\Email\LifecycleEmailService;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private LifecycleEmailService $emails) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'timezone' => ['required', 'string', 'timezone'],
            'terms' => ['required', 'accepted'],
            'cf-turnstile-response' => [new TurnstileRule()],
        ]);

        $trialDays = app(PlatformSettingsService::class)->getInt('DEFAULT_TRIAL_DAYS', 7);
        $freeModeEnabled = app(SubscriptionService::class)->isFreeModeEnabled();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'timezone' => $request->timezone,
            'subscription_plan' => 'free',
            'billing_interval' => $freeModeEnabled ? 'free' : null,
            'trial_ends_at' => $freeModeEnabled ? now()->addDays($trialDays) : null,
            'is_active' => $freeModeEnabled,
        ]);

        UserCredential::create(['user_id' => $user->id]);

        app(SettingsService::class)->seedUserParams($user->id);

        event(new Registered($user));

        Auth::login($user);
        $this->emails->sendWelcome($user);
        session()->flash('analytics_events', [
            ['name' => 'sign_up'],
        ]);

        return redirect('/dashboard');
    }
}
