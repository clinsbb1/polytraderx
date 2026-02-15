@extends('layouts.public')

@section('title', 'Confirm Password — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="ptx-auth-wrapper">
        <div class="ptx-auth-card">
            <h2>Confirm Password</h2>
            <p class="auth-subtitle">This is a secure area. Please confirm your password before continuing.</p>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="mb-4">
                    <label for="password" class="ptx-label">Password</label>
                    <input id="password" type="password" name="password" class="ptx-input" required autocomplete="current-password" placeholder="Enter your password">
                    @error('password')
                        <div class="ptx-input-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-ptx-primary w-100">Confirm</button>
            </form>
        </div>
    </div>
@endsection
