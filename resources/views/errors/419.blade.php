@extends('layouts.public')

@section('title', '419 — Session Expired')

@section('content')
<div class="ptx-auth-wrapper">
    <div class="ptx-auth-card" style="text-align: center; max-width: 480px;">
        <div style="font-size: 5rem; font-family: var(--font-display); font-weight: 800; color: var(--warning); line-height: 1; margin-bottom: 8px; text-shadow: 0 0 40px var(--warning-glow);">419</div>
        <h2 style="font-size: 1.5rem; margin-bottom: 12px;">Session Expired</h2>
        <p class="auth-subtitle" style="margin-bottom: 28px;">Your session has expired. Please refresh the page and try again.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-ptx-primary" onclick="event.preventDefault(); window.location.reload();">Refresh Page</a>
            <a href="{{ route('login') }}" class="btn btn-ptx-secondary">Log In</a>
        </div>
    </div>
</div>
@endsection
