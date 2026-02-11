@extends('layouts.admin')

@section('title', 'Polymarket Keys')

@section('content')
<h1 class="h3 mb-4">Polymarket API Keys</h1>

<div class="alert alert-info small">
    <i class="bi bi-shield-lock me-1"></i> All API keys are encrypted at rest. Leave fields blank to keep existing values.
</div>

{{-- Setup Guide --}}
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-book me-1"></i> How to Get Your Polymarket API Keys</h6>
    </div>
    <div class="card-body">
        <ol class="mb-0">
            <li class="mb-2">Go to <strong>polymarket.com</strong> and log in to your account.</li>
            <li class="mb-2">Navigate to <strong>Settings &gt; API Keys</strong> in your profile.</li>
            <li class="mb-2">Click <strong>"Create API Key"</strong> to generate a new key.</li>
            <li class="mb-2">Copy your <strong>API Key</strong> (UUID format), <strong>API Secret</strong> (base64 encoded), and <strong>Passphrase</strong> (hex string).</li>
            <li class="mb-2">Your <strong>Wallet Address</strong> is displayed on your Polymarket profile page (starts with <code>0x</code>).</li>
            <li>Paste all four values into the form below and click Save.</li>
        </ol>
    </div>
</div>

<form method="POST" action="/settings/credentials">
    @csrf

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Polymarket Credentials</h6>
            <span class="badge {{ $credential->hasPolymarketKeys() ? 'bg-success' : 'bg-warning' }}">
                {{ $credential->hasPolymarketKeys() ? 'Configured' : 'Not configured' }}
            </span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">API Key <small class="text-muted">(UUID format)</small></label>
                <input type="password" name="polymarket_api_key" class="form-control @error('polymarket_api_key') is-invalid @enderror"
                       placeholder="{{ $credential->polymarket_api_key ? '••••••• (saved)' : 'e.g. 12345678-abcd-efgh-ijkl-123456789012' }}">
                @error('polymarket_api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">API Secret <small class="text-muted">(base64 encoded)</small></label>
                <input type="password" name="polymarket_api_secret" class="form-control @error('polymarket_api_secret') is-invalid @enderror"
                       placeholder="{{ $credential->polymarket_api_secret ? '••••••• (saved)' : 'Base64 encoded string' }}">
                @error('polymarket_api_secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Passphrase <small class="text-muted">(hex string)</small></label>
                <input type="password" name="polymarket_api_passphrase" class="form-control @error('polymarket_api_passphrase') is-invalid @enderror"
                       placeholder="{{ $credential->polymarket_api_passphrase ? '••••••• (saved)' : 'Hex passphrase' }}">
                @error('polymarket_api_passphrase') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Wallet Address <small class="text-muted">(0x...)</small></label>
                <input type="text" name="polymarket_wallet_address" class="form-control @error('polymarket_wallet_address') is-invalid @enderror"
                       value="{{ $credential->polymarket_wallet_address ?? '' }}"
                       placeholder="0x1234...abcd">
                @error('polymarket_wallet_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Credentials</button>
</form>
@endsection
