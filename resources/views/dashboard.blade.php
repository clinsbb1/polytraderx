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
@if(!$telegramLinked && in_array($user->subscription_plan, ['pro', 'advanced', 'lifetime']))
    <div class="ptx-alert ptx-alert-info">
        <i class="bi bi-telegram"></i>
        <span><strong>Telegram not linked.</strong> <a href="/settings/telegram">Link your Telegram</a> to receive trade notifications.</span>
    </div>
@endif

@if($user->subscription_plan === 'free' && !$user->is_lifetime)
    @if($user->subscription_ends_at && $user->subscription_ends_at->isPast())
        <div class="ptx-alert ptx-alert-warning">
            <i class="bi bi-clock"></i>
            <span>
                <strong>Subscription Expired</strong> — You're on the Free plan. <a href="/subscription">Upgrade to unlock more features</a>.
            </span>
        </div>
    @endif
@endif

{{-- Getting Started Guide --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header" style="cursor: pointer;" onclick="document.getElementById('gettingStartedContent').classList.toggle('d-none')">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-rocket-takeoff me-2" style="color: var(--accent);"></i> New here? Start with this quick guide</h5>
            <i class="bi bi-chevron-down ms-3"></i>
        </div>
    </div>
    <div class="ptx-card-body d-none" id="gettingStartedContent">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div style="border-left: 3px solid var(--accent); padding-left: 1rem; margin-bottom: 1.5rem;">
                    <h6 style="color: var(--accent); margin-bottom: 0.5rem;">
                        <i class="bi bi-1-circle-fill me-2"></i>What is PolyTraderX?
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0;">
                        A <strong>simulation platform</strong> for testing trading strategies on Polymarket's 5-minute and 15-minute crypto prediction markets.
                        <span style="color: var(--accent);">No real money, no risk</span> — all trades are simulated using real market data.
                    </p>
                </div>

                <div style="border-left: 3px solid var(--profit); padding-left: 1rem; margin-bottom: 1.5rem;">
                    <h6 style="color: var(--profit); margin-bottom: 0.5rem;">
                        <i class="bi bi-2-circle-fill me-2"></i>Configure Your Strategy
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        Go to <a href="{{ route('strategy.index') }}">Strategy Parameters</a> to customize:
                    </p>
                    <ul style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0;">
                        <li><strong>Risk limits:</strong> Max bet size, daily loss limits</li>
                        <li><strong>Trading rules:</strong> Confidence thresholds, entry timing</li>
                        <li><strong>Market selection:</strong> Choose 5-min, 15-min, or both markets</li>
                        <li><strong>Assets:</strong> Which cryptos to trade (BTC, ETH, SOL)</li>
                    </ul>
                </div>

                <div style="border-left: 3px solid #ffc107; padding-left: 1rem;">
                    <h6 style="color: #ffc107; margin-bottom: 0.5rem;">
                        <i class="bi bi-3-circle-fill me-2"></i>Start the Simulator
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        In <a href="{{ route('strategy.index') }}">Strategy Parameters</a>, set <code>Simulator Enabled</code> to <span class="ptx-badge ptx-badge-success">true</span>
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0;">
                        The bot runs automatically every minute and scans markets based on your configured entry timing and strategy thresholds.
                    </p>
                </div>
            </div>

            <div class="col-md-6">
                <div style="border-left: 3px solid #00d4ff; padding-left: 1rem; margin-bottom: 1.5rem;">
                    <h6 style="color: #00d4ff; margin-bottom: 0.5rem;">
                        <i class="bi bi-4-circle-fill me-2"></i>Monitor Performance
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">Track your strategy's performance:</p>
                    <ul style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0;">
                        <li><strong>Dashboard:</strong> Real-time P&L, win rates, active trades</li>
                        <li><strong><a href="{{ route('trades.index') }}">Trades</a>:</strong> Full trade history with execution details</li>
                        <li><strong><a href="{{ route('balance.index') }}">Balance</a>:</strong> Equity curve and drawdown analysis</li>
                        <li><strong><a href="{{ route('logs.index') }}">Logs</a>:</strong> Detailed simulator activity logs</li>
                    </ul>
                </div>

                <div style="border-left: 3px solid #a855f7; padding-left: 1rem; margin-bottom: 1.5rem;">
                    <h6 style="color: #a855f7; margin-bottom: 0.5rem;">
                        <i class="bi bi-5-circle-fill me-2"></i>Optional: AI Analysis
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        The platform uses AI to analyze losing trades and suggest parameter improvements.
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0;">
                        Check <a href="{{ route('audits.index') }}">AI Audits</a> to review suggestions and approve/reject changes.
                    </p>
                </div>

                <div style="border-left: 3px solid var(--loss); padding-left: 1rem;">
                    <h6 style="color: var(--loss); margin-bottom: 0.5rem;">
                        <i class="bi bi-6-circle-fill me-2"></i>Reset & Experiment
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0;">
                        Not happy with results? Go to <a href="{{ route('balance.index') }}">Balance</a> and click <strong>Reset Balance</strong>
                        to clear your equity history and start fresh with a new starting balance.
                    </p>
                </div>
            </div>
        </div>

        <div class="alert mt-3 mb-0" style="background: linear-gradient(135deg, rgba(255,193,7,0.15) 0%, rgba(255,193,7,0.05) 100%); border: 2px solid #ffc107; border-radius: 8px; padding: 1rem 1.25rem;">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <i class="bi bi-lightbulb-fill" style="color: #ffc107; font-size: 1.5rem; flex-shrink: 0;"></i>
                <div>
                    <h6 style="color: #ffc107; margin-bottom: 0.5rem; font-weight: 600;">
                        💡 Pro Tip for Beginners
                    </h6>
                    <p style="color: var(--text-primary); margin-bottom: 0.5rem; line-height: 1.6;">
                        Start with conservative settings: <strong>low bet sizes ($5-10)</strong>, <strong>high confidence thresholds (0.92-0.95)</strong>,
                        and gradually optimize as you understand the markets better.
                    </p>
                    <p style="color: var(--text-secondary); margin-bottom: 0; font-size: 0.9rem;">
                        The platform simulates trades using <strong>real market data</strong> from Polymarket and Binance.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Row 1: Key Stats --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Today's P&L (Simulated)</div>
            <div class="stat-value {{ $todayPnl >= 0 ? 'text-profit' : 'text-loss' }}">{{ $todayPnl >= 0 ? '+' : '-' }}${{ number_format(abs($todayPnl), 2) }}</div>
            <div style="color: var(--text-secondary); font-size: 0.8rem;">{{ $todayTradeCount }} trades today</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Current Balance</div>
            <div class="stat-value text-accent">${{ $latestBalance ? number_format((float)$latestBalance->total_equity, 2) : '0.00' }}</div>
            <div style="color: var(--text-secondary); font-size: 0.75rem;">Simulated Balance</div>
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
            <div class="stat-label">Win Rate (7d) (Simulated)</div>
            <div class="stat-value text-accent">{{ $winRate7d }}%</div>
            <div style="color: var(--text-secondary); font-size: 0.75rem;">Today {{ $winRateToday }}% · 30d {{ $winRate30d }}%</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Strategy Health</div>
            <div class="stat-value text-accent">
                @if(isset($strategyHealth))
                    @if($strategyHealth === 'Insufficient_data')
                        No Data
                    @else
                        {{ ucfirst($strategyHealth) }}
                    @endif
                @else
                    N/A
                @endif
            </div>
            <div style="color: var(--text-secondary); font-size: 0.75rem;">Drawdown: {{ isset($maxDrawdown) ? number_format($maxDrawdown, 1) : '0.0' }}%</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Total Trades</div>
            <div class="stat-value text-accent">{{ $totalTrades ?? 0 }}</div>
            <div style="color: var(--text-secondary); font-size: 0.75rem;">{{ $totalWins ?? 0 }}W · {{ $totalLosses ?? 0 }}L</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Best Day (7d)</div>
            <div class="stat-value {{ ($bestDay7d ?? 0) >= 0 ? 'text-profit' : 'text-loss' }}">
                {{ ($bestDay7d ?? 0) >= 0 ? '+' : '' }}${{ number_format(abs($bestDay7d ?? 0), 2) }}
            </div>
            <div style="color: var(--text-secondary); font-size: 0.75rem;">{{ $bestDayDate ?? 'No data' }}</div>
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
                    <p>No trades yet. Simulator will evaluate signals when markets are in the entry window.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="ptx-card">
            <div class="ptx-card-header"><h5>Simulator Status</h5></div>
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
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Simulator</td><td class="text-end">@if($simulatorEnabled)<span class="ptx-badge ptx-badge-success">Active</span>@else<span class="ptx-badge ptx-badge-danger">Paused</span>@endif</td></tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Last heartbeat</td><td class="text-end" style="font-size:0.85rem">{{ $user->last_bot_heartbeat ? $user->last_bot_heartbeat->diffForHumans() : 'Never' }}</td></tr>
                    <tr><td style="color: var(--text-secondary); padding: 6px 0;">Telegram</td><td class="text-end">@if($telegramLinked)<span style="color:var(--profit)">Linked</span>@else<a href="/settings/telegram" style="font-size:0.85rem">Not linked</a>@endif</td></tr>
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
