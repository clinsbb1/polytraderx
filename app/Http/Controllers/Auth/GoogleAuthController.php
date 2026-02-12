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
            Auth::login($user);

            return redirect('/dashboard');
        }

        // Link by email if existing account
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);
            Auth::login($user);

            return redirect('/dashboard');
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
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays($trialDays),
        ]);

        UserCredential::create(['user_id' => $user->id]);

        app(SettingsService::class)->seedUserParams($user->id);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
