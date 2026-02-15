@extends('layouts.public')

@section('title', 'Forgot Password — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="ptx-auth-wrapper">
        <div class="ptx-auth-card">
            <h2>Reset Password</h2>
            <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>

            @if (session('status'))
                <div style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; color: var(--profit); font-size: 0.9rem;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="ptx-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="ptx-input" required autofocus placeholder="your@email.com">
                    @error('email')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <x-turnstile />

                <button type="submit" class="btn btn-ptx-primary w-100">Email Password Reset Link</button>
            </form>

            <p class="text-center mt-4" style="color: var(--text-secondary); font-size: 0.9rem;">
                Remember your password? <a href="{{ route('login') }}">Log in</a>
            </p>
        </div>
    </div>
@endsection
