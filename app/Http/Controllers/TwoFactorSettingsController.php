<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Security\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorSettingsController extends Controller
{
    private const SETUP_SECRET_SESSION_KEY = 'two_factor.pending_secret';

    public function __construct(private TotpService $totp) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $pendingSecret = (string) $request->session()->get(self::SETUP_SECRET_SESSION_KEY, '');
        $otpauthUri = null;
        $qrCodeUrl = null;

        if ($pendingSecret !== '') {
            $otpauthUri = $this->totp->provisioningUri('PolyTraderX', $user->email, $pendingSecret);
            $qrCodeUrl = $this->totp->qrCodeUrl($otpauthUri);
        }

        return view('settings.security', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'otpauthUri' => $otpauthUri,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $request->session()->put(self::SETUP_SECRET_SESSION_KEY, $this->totp->generateSecret());

        return redirect()->route('settings.security')->with('success', '2FA secret generated. Scan the QR code and confirm with a 6-digit code.');
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $pendingSecret = (string) $request->session()->get(self::SETUP_SECRET_SESSION_KEY, '');

        if ($pendingSecret === '') {
            return redirect()->route('settings.security')->with('error', 'Generate a new 2FA secret first.');
        }

        if (!$this->totp->verify($pendingSecret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid authentication code.'])->withInput();
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $pendingSecret,
            'two_factor_confirmed_at' => now(),
        ]);

        $request->session()->forget(self::SETUP_SECRET_SESSION_KEY);

        return redirect()->route('settings.security')->with('success', 'Two-factor authentication enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.security')->with('error', 'Two-factor authentication is not enabled.');
        }

        $secret = (string) ($user->two_factor_secret ?? '');
        if ($secret === '' || !$this->totp->verify($secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid authentication code.'])->withInput();
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $request->session()->forget(self::SETUP_SECRET_SESSION_KEY);

        return redirect()->route('settings.security')->with('success', 'Two-factor authentication disabled.');
    }
}

