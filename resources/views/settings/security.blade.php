@extends('layouts.admin')

@section('title', 'Account Security')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">Account Security</h4>

<div class="ptx-card" style="max-width: 760px;">
    <div class="ptx-card-body">
        <h6 class="mb-3">Google Authenticator 2FA</h6>

        @if($user->hasTwoFactorEnabled())
            <div class="alert alert-success mb-3">
                <strong>Enabled.</strong> Your account requires a 6-digit authenticator code at sign-in.
            </div>

            <form method="POST" action="{{ route('settings.security.2fa.disable') }}" style="max-width: 360px;">
                @csrf
                <div class="mb-3">
                    <label class="ptx-label">Current 2FA Code</label>
                    <input type="text" name="code" class="ptx-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="Enter 6-digit code">
                    @error('code') <div class="ptx-input-error">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-danger btn-sm">Disable 2FA</button>
            </form>
        @else
            <div class="alert alert-info mb-3">
                Add Google Authenticator to secure your account and admin access.
            </div>

            @if($pendingSecret === '')
                <form method="POST" action="{{ route('settings.security.2fa.generate') }}">
                    @csrf
                    <button type="submit" class="btn-ptx-primary btn-ptx-sm">Generate 2FA Setup</button>
                </form>
            @else
                <div class="mb-3">
                    <p class="mb-2" style="color: var(--text-secondary);">
                        1) Scan this QR code in Google Authenticator.
                    </p>
                    <img src="{{ $qrCodeUrl }}" alt="2FA QR Code" style="border-radius: 10px; border: 1px solid var(--border-subtle); background: #fff; padding: 10px; width: 220px; height: 220px;">
                </div>

                <div class="mb-3">
                    <p class="mb-2" style="color: var(--text-secondary);">2) Or enter this setup key manually:</p>
                    <code style="display: inline-block; padding: 8px 12px; border-radius: 8px; background: rgba(0,240,255,0.06); border: 1px solid rgba(0,240,255,0.15);">{{ $pendingSecret }}</code>
                </div>

                <form method="POST" action="{{ route('settings.security.2fa.enable') }}" style="max-width: 360px;">
                    @csrf
                    <div class="mb-3">
                        <label class="ptx-label">3) Enter 6-digit Code to Confirm</label>
                        <input type="text" name="code" class="ptx-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="Enter 6-digit code">
                        @error('code') <div class="ptx-input-error">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn-ptx-primary btn-ptx-sm">Enable 2FA</button>
                </form>
                <form method="POST" action="{{ route('settings.security.2fa.generate') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Setup Secret</button>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
