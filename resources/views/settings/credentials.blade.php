@extends('layouts.admin')

@section('title', 'Polymarket Keys')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">Polymarket API Keys</h4>

<div class="ptx-alert ptx-alert-info mb-4">
    <i class="bi bi-shield-lock"></i>
    <span>All API keys are encrypted at rest. Leave fields blank to keep existing values.</span>
</div>

{{-- Setup Guide --}}
<div class="ptx-info-card mb-4">
    <div class="info-header">
        <span><i class="bi bi-book me-2"></i> How to Get Your Polymarket API Keys</span>
    </div>
    <div class="info-body">
        <ol>
            <li>Go to <strong>polymarket.com</strong> and log in to your account.</li>
            <li>Navigate to <strong>Settings &gt; API Keys</strong> in your profile.</li>
            <li>Click <strong>"Create API Key"</strong> to generate a new key.</li>
            <li>Copy your <strong>API Key</strong> (UUID format), <strong>API Secret</strong> (base64 encoded), and <strong>Passphrase</strong> (hex string).</li>
            <li>Your <strong>Wallet Address</strong> is displayed on your Polymarket profile page (starts with <code>0x</code>).</li>
            <li>Paste all four values into the form below and click Save.</li>
        </ol>
    </div>
</div>

<form method="POST" action="/settings/credentials">
    @csrf

    <div class="ptx-info-card mb-4">
        <div class="info-header">
            <span>Polymarket Credentials</span>
            <span class="ptx-badge {{ $credential->hasPolymarketKeys() ? 'ptx-badge-success' : 'ptx-badge-warning' }}">
                {{ $credential->hasPolymarketKeys() ? 'Configured' : 'Not configured' }}
            </span>
        </div>
        <div class="info-body">
            <div class="mb-3">
                <label class="ptx-label">API Key <small style="color: var(--text-secondary);">(UUID format)</small></label>
                <input type="password" name="polymarket_api_key" class="ptx-input @error('polymarket_api_key') is-invalid @enderror"
                       placeholder="{{ $credential->polymarket_api_key ? '••••••• (saved)' : 'e.g. 12345678-abcd-efgh-ijkl-123456789012' }}">
                @error('polymarket_api_key') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="ptx-label">API Secret <small style="color: var(--text-secondary);">(base64 encoded)</small></label>
                <input type="password" name="polymarket_api_secret" class="ptx-input @error('polymarket_api_secret') is-invalid @enderror"
                       placeholder="{{ $credential->polymarket_api_secret ? '••••••• (saved)' : 'Base64 encoded string' }}">
                @error('polymarket_api_secret') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="ptx-label">Passphrase <small style="color: var(--text-secondary);">(hex string)</small></label>
                <input type="password" name="polymarket_api_passphrase" class="ptx-input @error('polymarket_api_passphrase') is-invalid @enderror"
                       placeholder="{{ $credential->polymarket_api_passphrase ? '••••••• (saved)' : 'Hex passphrase' }}">
                @error('polymarket_api_passphrase') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="ptx-label">Wallet Address <small style="color: var(--text-secondary);">(0x...)</small></label>
                <input type="text" name="polymarket_wallet_address" class="ptx-input @error('polymarket_wallet_address') is-invalid @enderror"
                       value="{{ $credential->polymarket_wallet_address ?? '' }}"
                       placeholder="0x1234...abcd">
                @error('polymarket_wallet_address') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <button type="submit" class="btn-ptx-primary btn-ptx-sm">Save Credentials</button>
</form>
@endsection
