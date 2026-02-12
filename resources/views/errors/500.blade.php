@extends('layouts.public')

@section('title', '500 — Server Error')

@section('content')
<div class="ptx-auth-wrapper">
    <div class="ptx-auth-card" style="text-align: center; max-width: 480px;">
        <div style="font-size: 5rem; font-family: var(--font-display); font-weight: 800; color: var(--loss); line-height: 1; margin-bottom: 8px; text-shadow: 0 0 40px rgba(255,71,87,0.3);">500</div>
        <h2 style="font-size: 1.5rem; margin-bottom: 12px;">Something Went Wrong</h2>
        <p class="auth-subtitle" style="margin-bottom: 28px;">We hit an unexpected error. Our team has been notified. Please try again shortly.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/" class="btn btn-ptx-primary">Go Home</a>
            @auth
                <a href="/dashboard" class="btn btn-ptx-secondary">Dashboard</a>
            @endauth
        </div>
    </div>
</div>
@endsection
