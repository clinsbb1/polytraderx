@extends('layouts.public')

@section('title', 'Register — PolyTraderX')

@section('content')
    <div class="ptx-auth-wrapper">
        <div class="ptx-auth-card">
            <h2>Create Account</h2>
            <p class="auth-subtitle">Start your 7-day free trial</p>

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

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="ptx-label">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="ptx-input" required autofocus autocomplete="name" placeholder="Your full name">
                    @error('name')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="ptx-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="ptx-input" required autocomplete="username" placeholder="your@email.com">
                    @error('email')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="password" class="ptx-label">Password</label>
                        <input id="password" type="password" name="password" class="ptx-input" required autocomplete="new-password" placeholder="Min 8 characters">
                        @error('password')
                            <div class="ptx-input-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="ptx-label">Confirm Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="ptx-input" required autocomplete="new-password" placeholder="Repeat password">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="timezone" class="ptx-label">Timezone</label>
                    <select id="timezone" name="timezone" class="ptx-input" required>
                        <option value="">Select your timezone</option>
                        @foreach(timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', 'Africa/Lagos') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="d-flex align-items-start gap-2" style="cursor: pointer; font-size: 0.85rem; color: var(--text-secondary);">
                        <input type="checkbox" name="terms" value="1" class="ptx-checkbox mt-1" {{ old('terms') ? 'checked' : '' }} required>
                        <span>I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a></span>
                    </label>
                    @error('terms')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-ptx-primary w-100">Create Account</button>
            </form>

            <p class="text-center mt-4" style="color: var(--text-secondary); font-size: 0.9rem;">
                Already have an account? <a href="{{ route('login') }}">Log in</a>
            </p>
        </div>
    </div>
@endsection
