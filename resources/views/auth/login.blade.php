@extends('layouts.public')

@section('title', 'Login — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="ptx-auth-wrapper">
        <div class="ptx-auth-card">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to your PolyTraderX account</p>

            @if (session('status'))
                <div style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; color: var(--profit); font-size: 0.9rem;">
                    {{ session('status') }}
                </div>
            @endif

            <a href="/auth/google" class="btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continue with Google
            </a>

            <div class="ptx-divider">or</div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="ptx-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="ptx-input" required autofocus autocomplete="username" placeholder="your@email.com">
                    @error('email')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="ptx-label">Password</label>
                    <input id="password" type="password" name="password" class="ptx-input" required autocomplete="current-password" placeholder="Enter your password">
                    @error('password')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <label class="d-flex align-items-center gap-2" style="cursor: pointer; font-size: 0.9rem; color: var(--text-secondary);">
                        <input type="checkbox" name="remember" class="ptx-checkbox">
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size: 0.9rem;">Forgot password?</a>
                    @endif
                </div>

                <x-turnstile />

                <button type="submit" class="btn btn-ptx-primary w-100">Sign In</button>
            </form>

            <p class="text-center mt-4" style="color: var(--text-secondary); font-size: 0.9rem;">
                Don't have an account? <a href="{{ route('register') }}">{{ $freeModeEnabled ? 'Sign up free' : 'Create account' }}</a>
            </p>
        </div>
    </div>
@endsection
