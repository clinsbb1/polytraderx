@extends('layouts.admin')

@section('title', 'Telegram Settings')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">Telegram Notifications</h4>

{{-- Account ID --}}
<div class="ptx-info-card mb-4">
    <div class="info-header">
        <span><i class="bi bi-person-badge me-2"></i> Your Account ID</span>
    </div>
    <div class="info-body">
        <p style="color: var(--text-secondary); font-size: 0.9rem;" class="mb-3">Use this Account ID to link your Telegram account to PolyTraderX.</p>
        <div class="input-group" style="max-width: 400px;">
            <input type="text" class="form-control font-monospace fw-bold" value="{{ $user->account_id }}" id="accountId" readonly>
            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('accountId').value); this.innerHTML='Copied!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy', 2000);">
                <i class="bi bi-clipboard"></i> Copy
            </button>
        </div>
    </div>
</div>

{{-- Link Status --}}
<div class="ptx-info-card mb-4">
    <div class="info-header">
        <span><i class="bi bi-telegram me-2"></i> Link Status</span>
        <span class="ptx-badge {{ $user->hasTelegramLinked() ? 'ptx-badge-success' : 'ptx-badge-secondary' }}">
            {{ $user->hasTelegramLinked() ? 'Linked' : 'Not linked' }}
        </span>
    </div>
    <div class="info-body">
        @if($user->hasTelegramLinked())
            <p style="color: var(--profit); font-size: 0.9rem;" class="mb-2">
                <i class="bi bi-check-circle me-1"></i>
                Your Telegram account is linked. You will receive trading notifications.
            </p>
            @if($user->telegram_username || $user->telegram_first_name)
                <p style="color: var(--text-primary); font-size: 0.9rem;" class="mb-2">
                    <i class="bi bi-telegram me-1" style="color: var(--accent);"></i>
                    <strong>{{ $user->telegram_username ? '@' . $user->telegram_username : $user->telegram_first_name }}</strong>
                </p>
            @endif
            <p style="color: var(--text-secondary); font-size: 0.85rem;" class="mb-3">
                Linked on: {{ $user->telegram_linked_at->format('F j, Y \a\t g:i A') }}
            </p>
            <form method="POST" action="/settings/telegram/unlink">
                @csrf
                <button type="submit" class="btn-ptx-danger btn-ptx-sm" onclick="return confirm('Are you sure you want to unlink your Telegram account?')">
                    <i class="bi bi-x-circle me-1"></i> Unlink Telegram
                </button>
            </form>
        @else
            <p style="color: var(--text-secondary); font-size: 0.9rem;" class="mb-0">Your Telegram is not linked. Follow the instructions below to set it up.</p>
        @endif
    </div>
</div>

{{-- Instructions --}}
@if(!$user->hasTelegramLinked())
<div class="ptx-info-card">
    <div class="info-header">
        <span><i class="bi bi-book me-2"></i> How to Link Telegram</span>
    </div>
    <div class="info-body">
        @php
            $botUsername = app(\App\Services\Settings\PlatformSettingsService::class)->get('TELEGRAM_BOT_USERNAME', 'PolyTraderXBot');
        @endphp
        <ol>
            <li>Open Telegram and search for <strong>{{ '@' . $botUsername }}</strong>.</li>
            <li>Start a conversation with the bot by clicking <strong>Start</strong>.</li>
            <li>Send the following command:<br>
                <code style="display: inline-block; margin-top: 6px; padding: 8px 14px; background: rgba(0,240,255,0.06); border: 1px solid rgba(0,240,255,0.12); border-radius: 6px;">/start {{ $user->account_id }}</code>
            </li>
            <li>The bot will confirm your account is linked.</li>
            <li>Refresh this page to see the updated status.</li>
        </ol>
    </div>
</div>
@endif
@endsection
