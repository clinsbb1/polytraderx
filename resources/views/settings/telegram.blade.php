@extends('layouts.admin')

@section('title', 'Telegram Settings')

@section('content')
<h1 class="h3 mb-4">Telegram Notifications</h1>

{{-- Account ID --}}
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-person-badge me-1"></i> Your Account ID</h6>
    </div>
    <div class="card-body">
        <p class="text-muted mb-2">Use this Account ID to link your Telegram account to PolyTraderX.</p>
        <div class="input-group" style="max-width: 400px;">
            <input type="text" class="form-control font-monospace fw-bold" value="{{ $user->account_id }}" id="accountId" readonly>
            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('accountId').value); this.innerHTML='Copied!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy', 2000);">
                <i class="bi bi-clipboard"></i> Copy
            </button>
        </div>
    </div>
</div>

{{-- Link Status --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-telegram me-1"></i> Link Status</h6>
        <span class="badge {{ $user->hasTelegramLinked() ? 'bg-success' : 'bg-secondary' }}">
            {{ $user->hasTelegramLinked() ? 'Linked' : 'Not linked' }}
        </span>
    </div>
    <div class="card-body">
        @if($user->hasTelegramLinked())
            <p class="text-success mb-2">
                <i class="bi bi-check-circle me-1"></i>
                Your Telegram account is linked. You will receive trading notifications.
            </p>
            <p class="text-muted small mb-3">
                Linked on: {{ $user->telegram_linked_at->format('F j, Y \a\t g:i A') }}
            </p>
            <form method="POST" action="/settings/telegram/unlink">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to unlink your Telegram account?')">
                    <i class="bi bi-x-circle me-1"></i> Unlink Telegram
                </button>
            </form>
        @else
            <p class="text-muted mb-0">Your Telegram is not linked. Follow the instructions below to set it up.</p>
        @endif
    </div>
</div>

{{-- Instructions --}}
@if(!$user->hasTelegramLinked())
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-book me-1"></i> How to Link Telegram</h6>
    </div>
    <div class="card-body">
        @php
            $botUsername = app(\App\Services\Settings\PlatformSettingsService::class)->get('TELEGRAM_BOT_USERNAME', 'PolyTraderXBot');
        @endphp
        <ol class="mb-0">
            <li class="mb-2">Open Telegram and search for <strong>@{{ $botUsername }}</strong>.</li>
            <li class="mb-2">Start a conversation with the bot by clicking <strong>Start</strong>.</li>
            <li class="mb-2">Send the following command:<br>
                <code class="d-inline-block mt-1 p-2 bg-light rounded">/start {{ $user->account_id }}</code>
            </li>
            <li class="mb-2">The bot will confirm your account is linked.</li>
            <li>Refresh this page to see the updated status.</li>
        </ol>
    </div>
</div>
@endif
@endsection
