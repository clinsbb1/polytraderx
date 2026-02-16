<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const SESSION_USER_KEY = 'two_factor.login.user_id';
    private const SESSION_REMEMBER_KEY = 'two_factor.login.remember';

    public function __construct(private TotpService $totp) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has(self::SESSION_USER_KEY)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = (int) $request->session()->get(self::SESSION_USER_KEY, 0);
        $remember = (bool) $request->session()->get(self::SESSION_REMEMBER_KEY, false);

        if ($userId <= 0) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user || !$user->hasTwoFactorEnabled()) {
            $request->session()->forget([self::SESSION_USER_KEY, self::SESSION_REMEMBER_KEY]);

            return redirect()->route('login')->withErrors(['email' => 'Your session expired. Please sign in again.']);
        }

        $secret = (string) ($user->two_factor_secret ?? '');
        if ($secret === '' || !$this->totp->verify($secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid authentication code.'])->withInput();
        }

        Auth::login($user, $remember);
        $request->session()->forget([self::SESSION_USER_KEY, self::SESSION_REMEMBER_KEY]);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('toast', 'Welcome back, ' . $user->name . '!');
    }
}

