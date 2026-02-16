<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        // Find by google_id first
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            $user->update(['avatar_url' => $googleUser->getAvatar()]);
            return $this->completeLogin($user, true);
        }

        // Link by email if existing account
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);
            return $this->completeLogin($user, true);
        }

        // Create new user
        $trialDays = app(PlatformSettingsService::class)->getInt('DEFAULT_TRIAL_DAYS', 7);

        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'password' => bcrypt(Str::random(32)),
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
            'subscription_plan' => 'free',
            'trial_ends_at' => now()->addDays($trialDays),
        ]);

        UserCredential::create(['user_id' => $user->id]);

        app(SettingsService::class)->seedUserParams($user->id);

        return $this->completeLogin($user, false, [
            ['name' => 'sign_up'],
        ]);
    }

    private function completeLogin(User $user, bool $isReturningUser, array $analyticsEvents = []): RedirectResponse
    {
        Auth::login($user);

        if (!empty($analyticsEvents)) {
            session()->flash('analytics_events', $analyticsEvents);
        }

        if ($user->hasTwoFactorEnabled()) {
            session()->put([
                'two_factor.login.user_id' => $user->id,
                'two_factor.login.remember' => false,
            ]);
            Auth::logout();
            session()->regenerate();

            return redirect()->route('2fa.challenge');
        }

        $message = $isReturningUser
            ? 'Welcome back, ' . $user->name . '!'
            : 'Welcome to PolyTraderX, ' . $user->name . '!';

        return redirect('/dashboard')->with('toast', $message);
    }
}
