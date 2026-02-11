@extends('layouts.admin')

@section('title', 'API Credentials')

@section('content')
<h1 class="h3 mb-4">API Credentials</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="alert alert-info small">
    <i class="bi bi-shield-lock me-1"></i> All API keys are encrypted at rest. Leave fields blank to keep existing values.
</div>

<form method="POST" action="/settings/credentials">
    @csrf

    <!-- Polymarket -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Polymarket</h6></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">API Key</label>
                <input type="password" name="polymarket_api_key" class="form-control" placeholder="{{ $credential->polymarket_api_key ? '••••••• (saved)' : 'Not configured' }}">
            </div>
            <div class="mb-3">
                <label class="form-label">API Secret</label>
                <input type="password" name="polymarket_api_secret" class="form-control" placeholder="{{ $credential->polymarket_api_secret ? '••••••• (saved)' : 'Not configured' }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Passphrase</label>
                <input type="password" name="polymarket_passphrase" class="form-control" placeholder="{{ $credential->polymarket_passphrase ? '••••••• (saved)' : 'Not configured' }}">
            </div>
            <span class="badge {{ $credential->hasPolymarketKeys() ? 'bg-success' : 'bg-warning' }}">
                {{ $credential->hasPolymarketKeys() ? 'Configured' : 'Not configured' }}
            </span>
        </div>
    </div>

    <!-- Telegram -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Telegram</h6></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Bot Token</label>
                <input type="password" name="telegram_bot_token" class="form-control" placeholder="{{ $credential->telegram_bot_token ? '••••••• (saved)' : 'Not configured' }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Chat ID</label>
                <input type="text" name="telegram_chat_id" class="form-control" placeholder="{{ $credential->telegram_chat_id ? '••••••• (saved)' : 'Not configured' }}">
            </div>
            <span class="badge {{ $credential->hasTelegramKeys() ? 'bg-success' : 'bg-secondary' }}">
                {{ $credential->hasTelegramKeys() ? 'Configured' : 'Optional' }}
            </span>
        </div>
    </div>

    <!-- Anthropic -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Anthropic</h6></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">API Key</label>
                <input type="password" name="anthropic_api_key" class="form-control" placeholder="{{ $credential->anthropic_api_key ? '••••••• (saved)' : 'Not configured' }}">
            </div>
            <span class="badge {{ $credential->hasAnthropicKey() ? 'bg-success' : 'bg-secondary' }}">
                {{ $credential->hasAnthropicKey() ? 'Configured' : 'Optional' }}
            </span>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Credentials</button>
</form>
@endsection
