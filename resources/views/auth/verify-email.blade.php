@extends('layouts.public')

@section('title', 'Verify Email — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="ptx-auth-wrapper">
        <div class="ptx-auth-card">
            <h2>Verify Email</h2>
            <p class="auth-subtitle">Thanks for signing up! Please verify your email address by clicking the link we just sent you.</p>

            @if (session('status') == 'verification-link-sent')
                <div style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; color: var(--profit); font-size: 0.9rem;">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-between mt-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-ptx-primary">Resend Verification Email</button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ptx-secondary">Log Out</button>
                </form>
            </div>
        </div>
    </div>
@endsection
