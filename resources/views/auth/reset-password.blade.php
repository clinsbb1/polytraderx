@extends('layouts.public')

@section('title', 'Reset Password — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="ptx-auth-wrapper">
        <div class="ptx-auth-card">
            <h2>Set New Password</h2>
            <p class="auth-subtitle">Choose a new password for your account</p>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    <label for="email" class="ptx-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="ptx-input" required autofocus autocomplete="username">
                    @error('email')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="ptx-label">New Password</label>
                    <input id="password" type="password" name="password" class="ptx-input" required autocomplete="new-password" placeholder="Min 8 characters">
                    @error('password')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="ptx-label">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="ptx-input" required autocomplete="new-password" placeholder="Repeat password">
                </div>

                <button type="submit" class="btn btn-ptx-primary w-100">Reset Password</button>
            </form>
        </div>
    </div>
@endsection
