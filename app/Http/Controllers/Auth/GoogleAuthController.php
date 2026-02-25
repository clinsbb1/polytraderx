<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with(
                'status',
                'Google sign-in failed. Please try again or sign in with email/password.'
            );
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        if ($email === '') {
            return redirect()->route('login')->with(
                'status',
                'Google did not provide an email for this account. Please sign in with email/password.'
            );
        }

        // Find by google_id first
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            $user->update(['avatar_url' => $googleUser->getAvatar()]);
            return $this->completeLogin($user, true);
        }

        // Existing email account without Google linkage: do not auto-link.
        $user = User::where('email', $email)->first();

        if ($user) {
            return redirect()->route('login')->with(
                'status',
                'This email already has an account, but Google is not linked to it. Sign in with email/password.'
            );
        }

        // Create new user
        $trialDays = app(PlatformSettingsService::class)->getInt('DEFAULT_TRIAL_DAYS', 7);
        $freeModeEnabled = app(SubscriptionService::class)->isFreeModeEnabled();

        try {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Google User',
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'subscription_plan' => 'free',
                'billing_interval' => $freeModeEnabled ? 'free' : null,
                'trial_ends_at' => $freeModeEnabled ? now()->addDays($trialDays) : null,
                'is_active' => $freeModeEnabled,
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::warning('Google OAuth user creation failed', ['email' => $email, 'error' => $e->getMessage()]);

            return redirect()->route('login')->with(
                'status',
                'Could not complete Google sign-in right now. Please try again or sign in with email/password.'
            );
        }

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
