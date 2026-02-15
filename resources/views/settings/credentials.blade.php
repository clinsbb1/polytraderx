@extends('layouts.admin')

@section('title', 'API Credentials')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">API Credentials</h4>

<div class="ptx-info-card">
    <div class="info-header">
        <span><i class="bi bi-shield-check me-2"></i> Simulation-Only Platform</span>
    </div>
    <div class="info-body">
        <p class="mb-3">
            <strong>PolyTraderX operates exclusively in simulation mode.</strong> You don't need to provide any Polymarket API keys or credentials.
        </p>

        <div class="mb-3">
            <h6 class="fw-bold mb-2">How It Works:</h6>
            <ul class="mb-0">
                <li>All market data is fetched using platform-wide API keys</li>
                <li>All "trades" are simulated using real market data</li>
                <li>No real orders are placed on Polymarket</li>
                <li>No funds are moved or at risk</li>
                <li>No private keys or wallet access required</li>
            </ul>
        </div>

        <div class="ptx-alert ptx-alert-success mt-3 mb-0">
            <i class="bi bi-check-circle"></i>
            <span>You're all set! No additional credentials needed to use PolyTraderX.</span>
        </div>
    </div>
</div>

<div class="ptx-info-card mt-4">
    <div class="info-header">
        <span><i class="bi bi-lightbulb me-2"></i> Future Live Trading</span>
    </div>
    <div class="info-body">
        <p class="mb-0">
            If live trading is enabled in the future, you'll be able to configure your Polymarket credentials here.
            For now, focus on testing and refining your strategies risk-free in simulation mode.
        </p>
    </div>
</div>
@endsection
