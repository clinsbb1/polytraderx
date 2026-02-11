@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
{{-- Announcements --}}
@foreach($announcements as $announcement)
    <div class="alert alert-{{ $announcement->type }} alert-dismissible fade show" role="alert">
        <strong>{{ $announcement->title }}</strong> — {{ $announcement->body }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endforeach

{{-- Setup Banners --}}
@if(!$credentialsConfigured)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Polymarket keys not configured.</strong>
        <a href="/settings/credentials" class="alert-link">Set up your API keys</a> to start trading.
    </div>
@endif

@if(!$telegramLinked)
    <div class="alert alert-info">
        <i class="bi bi-telegram me-1"></i>
        <strong>Telegram not linked.</strong>
        <a href="/settings/telegram" class="alert-link">Link your Telegram</a> to receive trade notifications.
    </div>
@endif

{{-- Subscription Status --}}
@if(auth()->user()->subscription_plan === 'free_trial')
    <div class="alert alert-info">
        <i class="bi bi-clock me-1"></i>
        <strong>Free Trial</strong> —
        @if(auth()->user()->isTrialExpired())
            Your trial has expired. <a href="/subscription" class="alert-link">Upgrade now</a> to continue trading.
        @else
            {{ auth()->user()->daysLeftInTrial() }} days remaining.
            <a href="/subscription" class="alert-link">View plans</a>
        @endif
    </div>
@endif

{{-- Account ID --}}
<div class="row mb-4">
    <div class="col-auto">
        <span class="text-muted small">Account ID:</span>
        <code class="ms-1">{{ $accountId }}</code>
    </div>
</div>

{{-- Stats Cards --}}
<div class="row g-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Total P&L</h6>
                <h2 class="{{ (float)$stats['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format((float)$stats['total_pnl'], 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Win Rate</h6>
                <h2>{{ $stats['total_trades'] > 0 ? number_format(($stats['won_trades'] / max($stats['won_trades'] + $stats['lost_trades'], 1)) * 100, 1) : '0' }}%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Today's Trades</h6>
                <h2>{{ $stats['today_trades'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Open Positions</h6>
                <h2>{{ $stats['open_trades'] }}</h2>
            </div>
        </div>
    </div>
</div>

{{-- Recent Trades --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Trades</h5>
                <a href="{{ route('trades.index') }}" class="small">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentTrades->count() > 0)
                <table class="table table-sm mb-0">
                    <thead><tr><th>Asset</th><th>Side</th><th>Amount</th><th>Status</th><th>P&L</th><th>Time</th></tr></thead>
                    <tbody>
                        @foreach($recentTrades as $trade)
                        <tr>
                            <td><a href="{{ route('trades.show', $trade) }}">{{ $trade->asset }}</a></td>
                            <td>{{ $trade->side }}</td>
                            <td>${{ number_format((float)$trade->amount, 2) }}</td>
                            <td><span class="badge bg-{{ $trade->status === 'won' ? 'success' : ($trade->status === 'lost' ? 'danger' : ($trade->status === 'open' ? 'info' : 'secondary')) }}">{{ $trade->status }}</span></td>
                            <td class="{{ (float)($trade->pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ $trade->pnl !== null ? '$' . number_format((float)$trade->pnl, 2) : '—' }}</td>
                            <td class="small text-muted">{{ $trade->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="p-4 text-center text-muted">
                    No trades yet. The bot will populate this when it starts trading.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
