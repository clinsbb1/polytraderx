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

{{-- Status Banners --}}
@if(!$botEnabled)
    <div class="ptx-alert ptx-alert-danger">
        <i class="bi bi-pause-circle"></i>
        <span><strong>Bot is paused.</strong> <a href="{{ route('strategy.index') }}">Enable in Strategy settings</a> to start trading.</span>
    </div>
@endif

@if(!$credentialsConfigured)
    <div class="ptx-alert ptx-alert-warning">
        <i class="bi bi-link-45deg"></i>
        <span><strong>Polymarket keys not configured.</strong> <a href="/settings/credentials">Set up your API keys</a> to start trading.</span>
    </div>
@endif

@if(!$telegramLinked)
    <div class="ptx-alert ptx-alert-info">
        <i class="bi bi-telegram"></i>
        <span><strong>Telegram not linked.</strong> <a href="/settings/telegram">Link your Telegram</a> to receive trade notifications.</span>
    </div>
@endif

@if($user->subscription_plan === 'free_trial')
    <div class="ptx-alert ptx-alert-info">
        <i class="bi bi-clock"></i>
        <span>
            <strong>Free Trial</strong> —
            @if($user->isTrialExpired())
                Your trial has expired. <a href="/subscription">Upgrade now</a>.
            @else
                {{ $user->daysLeftInTrial() }} days remaining. <a href="/subscription">View plans</a>
            @endif
        </span>
    </div>
@endif

{{-- Row 1: Key Stats --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Today's P&L</div>
            <div class="stat-value {{ $todayPnl >= 0 ? 'text-profit' : 'text-loss' }}">{{ $todayPnl >= 0 ? '+' : '-' }}${{ number_format(abs($todayPnl), 2) }}</div>
            <div style="color: var(--text-secondary); font-size: 0.8rem;">{{ $todayTradeCount }} trades today</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Current Balance</div>
            <div class="stat-value text-accent">${{ $latestBalance ? number_format((float)$latestBalance->total_equity, 2) : '0.00' }}</div>
            @if($dryRun)<div style="color: var(--warning); font-size: 0.75rem;">Simulated</div>@endif
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Open Positions</div>
            <div class="stat-value text-accent">{{ $openPositions->count() }}</div>
            <div style="color: var(--text-secondary); font-size: 0.8rem;">{{ $openPositions->pluck('asset')->unique()->implode(', ') ?: 'None' }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Win Rate (7d)</div>
            <div class="stat-value text-accent">{{ $winRate7d }}%</div>
            <div style="color: var(--text-secondary); font-size: 0.75rem;">Today {{ $winRateToday }}% · 30d {{ $winRate30d }}%</div>
        </div>
    </div>
</div>

{{-- Row 2: Charts --}}
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="ptx-card">
            <div class="ptx-card-header"><h5>Equity Curve (30d)</h5></div>
            <div class="ptx-card-body">
                @if($equityCurve->count() > 1)
                    <canvas id="equityChart" height="200"></canvas>
                @else
                    <div class="ptx-empty-state"><i class="bi bi-graph-up d-block"></i><p>Equity chart appears once balance snapshots are recorded.</p></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="ptx-card">
            <div class="ptx-card-header"><h5>Daily P&L (14d)</h5></div>
            <div class="ptx-card-body">
                @if($dailyPnl->count() > 0)
                    <canvas id="pnlChart" height="200"></canvas>
                @else
                    <div class="ptx-empty-state"><i class="bi bi-bar-chart d-block"></i><p>Daily P&L chart appears after your first trading day.</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Recent Trades + Bot Status --}}
<div class="row g-4">
    <div class="col-lg-7">
        <div class="ptx-card">
            <div class="ptx-card-header">
                <h5>Last 10 Trades</h5>
                <a href="{{ route('trades.index') }}" class="small">View All</a>
            </div>
            <div class="ptx-card-body p-0">
                @if($recentTrades->count() > 0)
                <table class="ptx-table">
                    <thead>
                        <tr><th>Time</th><th>Asset</th><th>Side</th><th>Amount</th><th>P&L</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentTrades as $trade)
                        <tr onclick="window.location='{{ route('trades.show', $trade) }}'" style="cursor:pointer">
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $trade->created_at->diffForHumans() }}</td>
                            <td>{{ $trade->asset }}</td>
                            <td><span class="ptx-badge {{ $trade->side === 'YES' ? 'ptx-badge-success' : 'ptx-badge-danger' }}">{{ $trade->side }}</span></td>
                            <td>${{ number_format((float)$trade->amount, 2) }}</td>
                            <td style="color: {{ (float)($trade->pnl ?? 0) >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-weight: 600;">
                                {{ $trade->pnl !== null ? '$' . number_format((float)$trade->pnl, 2) : '—' }}
                            </td>
                            <td>
                                <span class="ptx-badge ptx-badge-{{ $trade->status === 'won' ? 'success' : ($trade->status === 'lost' ? 'danger' : ($trade->status === 'open' ? 'info' : 'secondary')) }}">{{ $trade->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="ptx-empty-state">
                    <i class="bi bi-currency-exchange d-block"></i>
                    <p>No trades yet. Your bot will start trading when markets are in the entry window.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="ptx-card">
            <div class="ptx-card-header"><h5>Bot Status</h5></div>
            <div class="ptx-card-body">
                <table class="w-100" style="font-size: 0.9rem;">
                    <tr>
                        <td style="color: var(--text-secondary); padding: 6px 0;">Account ID</td>
                        <td class="text-end"><span class="ptx-account-id" style="font-size:0.8rem">{{ $user->account_id }}</span></td>
                    </tr>
                    <tr>
                        <td style="color: var(--text-secondary); padding: 6px 0;">Plan</td>
                        <td class="text-end">
                            @php $plan = $user->currentPlan(); @endphp
                            {{ $plan ? $plan->name : 'None' }}
                            @if($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
                                <span style="color:var(--text-secondary); font-size:0.8rem"> — {{ $user->subscription_ends_at->diffForHumans() }}</span>
                            @elseif($user->trial_ends_at && $user->trial_ends_at->isFuture())
                                <span style="color:var(--text-secondary); font-size:0.8rem"> — {{ $user->daysLeftInTrial() }}d left</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Bot</td><td class="text-end">@if($botEnabled)<span class="ptx-badge ptx-badge-success">Active</span>@else<span class="ptx-badge ptx-badge-danger">Paused</span>@endif</td></tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Mode</td><td class="text-end">@if($dryRun)<span class="ptx-badge ptx-badge-warning">DRY RUN</span>@else<span class="ptx-badge ptx-badge-success">LIVE</span>@endif</td></tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Last heartbeat</td><td class="text-end" style="font-size:0.85rem">{{ $user->last_bot_heartbeat ? $user->last_bot_heartbeat->diffForHumans() : 'Never' }}</td></tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Telegram</td><td class="text-end">@if($telegramLinked)<span style="color:var(--profit)">Linked</span>@else<a href="/settings/telegram" style="font-size:0.85rem">Not linked</a>@endif</td></tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Polymarket</td><td class="text-end">@if($credentialsConfigured)<span style="color:var(--profit)">Connected</span>@else<a href="/settings/credentials" style="font-size:0.85rem">Not configured</a>@endif</td></tr>
                    @if($pendingAudits > 0)
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Pending audits</td><td class="text-end"><a href="{{ route('audits.index') }}">{{ $pendingAudits }} pending</a></td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($equityCurve->count() > 1)
new Chart(document.getElementById('equityChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($equityCurve->pluck('date')) !!},
        datasets: [{
            label: 'Total Equity',
            data: {!! json_encode($equityCurve->pluck('equity')) !!},
            borderColor: '#00f0ff',
            backgroundColor: 'rgba(0,240,255,0.08)',
            fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888a0', font: { size: 10 } } },
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888a0', callback: v => '$' + v } }
        }
    }
});
@endif
@if($dailyPnl->count() > 0)
new Chart(document.getElementById('pnlChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($dailyPnl->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!},
        datasets: [{
            label: 'P&L',
            data: {!! json_encode($dailyPnl->pluck('gross_pnl')) !!},
            backgroundColor: {!! json_encode($dailyPnl->pluck('gross_pnl')->map(fn($v) => (float)$v >= 0 ? 'rgba(0,230,118,0.6)' : 'rgba(255,71,87,0.6)')) !!},
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#8888a0', font: { size: 10 } } },
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888a0', callback: v => '$' + v } }
        }
    }
});
@endif
</script>
@endpush
