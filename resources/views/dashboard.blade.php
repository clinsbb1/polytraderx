@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
{{-- Announcements --}}
@foreach($announcements as $announcement)
    <div class="ptx-alert ptx-alert-{{ $announcement->type }}" role="alert">
        <i class="bi bi-{{ $announcement->type === 'warning' ? 'exclamation-triangle-fill' : ($announcement->type === 'danger' ? 'x-circle-fill' : ($announcement->type === 'success' ? 'check-circle-fill' : 'info-circle-fill')) }}"></i>
        <span><strong>{{ $announcement->title }}</strong> — {{ $announcement->body }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endforeach

{{-- Setup Banners --}}
@if(!$credentialsConfigured)
    <div class="ptx-alert ptx-alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <span><strong>Polymarket keys not configured.</strong> <a href="/settings/credentials">Set up your API keys</a> to start trading.</span>
    </div>
@endif

@if(!$telegramLinked)
    <div class="ptx-alert ptx-alert-info">
        <i class="bi bi-telegram"></i>
        <span><strong>Telegram not linked.</strong> <a href="/settings/telegram">Link your Telegram</a> to receive trade notifications.</span>
    </div>
@endif

{{-- Subscription Status --}}
@if(auth()->user()->subscription_plan === 'free_trial')
    <div class="ptx-alert ptx-alert-info">
        <i class="bi bi-clock"></i>
        <span>
            <strong>Free Trial</strong> —
            @if(auth()->user()->isTrialExpired())
                Your trial has expired. <a href="/subscription">Upgrade now</a> to continue trading.
            @else
                {{ auth()->user()->daysLeftInTrial() }} days remaining.
                <a href="/subscription">View plans</a>
            @endif
        </span>
    </div>
@endif

{{-- Account ID --}}
<div class="mb-4">
    <span style="color: var(--text-secondary); font-size: 0.85rem;" class="me-2">Account ID:</span>
    <span class="ptx-account-id">{{ $accountId }}</span>
</div>

{{-- Stats Cards --}}
<div class="row g-4">
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Total P&L</div>
            <div class="stat-value {{ (float)$stats['total_pnl'] >= 0 ? 'text-profit' : 'text-loss' }}">${{ number_format((float)$stats['total_pnl'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Win Rate</div>
            <div class="stat-value text-accent">{{ $stats['total_trades'] > 0 ? number_format(($stats['won_trades'] / max($stats['won_trades'] + $stats['lost_trades'], 1)) * 100, 1) : '0' }}%</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Today's Trades</div>
            <div class="stat-value text-accent">{{ $stats['today_trades'] }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Open Positions</div>
            <div class="stat-value text-accent">{{ $stats['open_trades'] }}</div>
        </div>
    </div>
</div>

{{-- Recent Trades --}}
<div class="ptx-card mt-4">
    <div class="ptx-card-header">
        <h5>Recent Trades</h5>
        <a href="{{ route('trades.index') }}" class="small">View All</a>
    </div>
    <div class="ptx-card-body p-0">
        @if($recentTrades->count() > 0)
        <table class="ptx-table">
            <thead>
                <tr>
                    <th>Asset</th>
                    <th>Side</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>P&L</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentTrades as $trade)
                <tr>
                    <td><a href="{{ route('trades.show', $trade) }}">{{ $trade->asset }}</a></td>
                    <td>{{ $trade->side }}</td>
                    <td>${{ number_format((float)$trade->amount, 2) }}</td>
                    <td>
                        <span class="ptx-badge ptx-badge-{{ $trade->status === 'won' ? 'success' : ($trade->status === 'lost' ? 'danger' : ($trade->status === 'open' ? 'info' : 'secondary')) }}">
                            {{ $trade->status }}
                        </span>
                    </td>
                    <td style="color: {{ (float)($trade->pnl ?? 0) >= 0 ? 'var(--profit)' : 'var(--loss)' }}">
                        {{ $trade->pnl !== null ? '$' . number_format((float)$trade->pnl, 2) : '—' }}
                    </td>
                    <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $trade->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="ptx-empty-state">
            <i class="bi bi-currency-exchange d-block"></i>
            <p>No trades yet. The bot will populate this when it starts trading.</p>
        </div>
        @endif
    </div>
</div>
@endsection
