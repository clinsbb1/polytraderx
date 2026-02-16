@extends('layouts.public')

@section('title', 'Two-Factor Verification — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="ptx-auth-wrapper">
    <div class="ptx-auth-card">
        <h2>Verify Sign-In</h2>
        <p class="auth-subtitle">Enter the 6-digit code from your Google Authenticator app.</p>

        @if (session('status'))
            <div style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; color: var(--profit); font-size: 0.9rem;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf

            <div class="mb-3">
                <label for="code" class="ptx-label">Authentication Code</label>
                <input id="code" type="text" name="code" class="ptx-input" required autofocus inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456">
                @error('code')
                    <div class="ptx-input-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-ptx-primary w-100">Verify</button>
        </form>

        <p class="text-center mt-4" style="color: var(--text-secondary); font-size: 0.9rem;">
            Not your account? <a href="{{ route('login') }}">Back to login</a>
        </p>
    </div>
</div>
@endsection

