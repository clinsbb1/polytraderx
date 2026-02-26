@extends('layouts.admin')

@section('title', 'Balance & Equity')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0" style="font-family: var(--font-display);">Balance & Equity</h4>
    <div class="d-flex gap-2">
    <a href="{{ route('balance.daily-summaries') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar2-week me-1"></i> Daily Summaries
    </a>
    @if(($openTradeCount ?? 0) > 0)
        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Close open/pending trades first">
            <i class="bi bi-lock-fill me-1"></i> Reset Unavailable ({{ $openTradeCount }})
        </button>
    @else
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="showResetModal()">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Balance
        </button>
    @endif
    </div>
</div>

<div class="ptx-info-card mb-4" style="background: var(--card-bg); border-left: 4px solid var(--accent);">
    <div class="info-body">
        <p class="mb-0">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Simulated Figures:</strong> All balances, equity, and P&L shown below are from simulated trades. No real money is involved.
        </p>
    </div>
</div>

@if(($openTradeCount ?? 0) > 0)
<div class="ptx-info-card mb-4" style="background: var(--card-bg); border-left: 4px solid #ffc107;">
    <div class="info-body">
        <p class="mb-1">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Balance reset is currently locked.</strong> You have {{ $openTradeCount }} open/pending trade(s).
        </p>
        <p class="mb-0" style="color: var(--text-secondary);">
            Reset is blocked while trades are active so simulation accounting stays accurate.
            Turn off the simulator in <a href="{{ route('strategy.index') }}" style="color: var(--accent);">Strategy</a> (or from the sidebar toggle), then reset after those trades resolve.
        </p>
    </div>
</div>
@endif

{{-- Row 1: Stat Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Current Balance (Simulated)</div>
            <div class="stat-value text-accent">${{ $latestSnapshot ? number_format((float)$latestSnapshot->balance_usdc, 2) : '100.00' }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Open Positions (Simulated)</div>
            <div class="stat-value text-accent">${{ $latestSnapshot ? number_format((float)$latestSnapshot->open_positions_value, 2) : '0.00' }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Total Equity (Simulated)</div>
            <div class="stat-value text-accent">${{ $latestSnapshot ? number_format((float)$latestSnapshot->total_equity, 2) : '100.00' }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Starting Balance</div>
            <div class="stat-value text-accent">${{ $anchorSnapshot ? number_format((float)$anchorSnapshot->balance_usdc, 2) : '100.00' }}</div>
            @php
                $anchorAmount = (float) ($anchorSnapshot->balance_usdc ?? 100);
                $currentEquity = (float) ($latestSnapshot->total_equity ?? $anchorAmount);
                $pnlSinceStart = $currentEquity - $anchorAmount;
                $pnlPct = $anchorAmount > 0 ? ($pnlSinceStart / $anchorAmount) * 100 : 0;
            @endphp
            <div style="color: {{ $pnlSinceStart >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-size: 0.8rem; font-weight: 600; margin-top: 4px;">
                {{ $pnlSinceStart >= 0 ? '+' : '' }}${{ number_format($pnlSinceStart, 2) }}
                ({{ $pnlSinceStart >= 0 ? '+' : '' }}{{ number_format($pnlPct, 1) }}%)
            </div>
            <div style="color: var(--text-secondary); font-size: 0.72rem; margin-top: 2px;">
                since {{ $anchorSnapshot ? \Carbon\Carbon::parse($anchorSnapshot->snapshot_at)->format('M j, Y') : 'default' }}
            </div>
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
                    <div class="ptx-empty-state">
                        <i class="bi bi-graph-up d-block"></i>
                        <p>Equity chart appears once balance snapshots are recorded over multiple days.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="ptx-card">
            <div class="ptx-card-header"><h5>Daily P&L (30d)</h5></div>
            <div class="ptx-card-body">
                @if($dailyPnl->count() > 0)
                    <canvas id="pnlChart" height="200"></canvas>
                @else
                    <div class="ptx-empty-state">
                        <i class="bi bi-bar-chart d-block"></i>
                        <p>Daily P&L chart appears after your first trading day.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Last 7 Days Stats --}}
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Last 7 Days</h5>
    </div>
    <div class="ptx-card-body p-0">
        @if($weeklyStatsWithCumulative->count() > 0)
            <table class="ptx-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Trades</th>
                        <th>Wins</th>
                        <th>Losses</th>
                        <th>Win Rate</th>
                        <th>P&L</th>
                        <th>Cumulative P&L</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($weeklyStatsWithCumulative as $day)
                        <tr>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $day->date->format('M d, Y') }}</td>
                            <td>{{ $day->total_trades }}</td>
                            <td style="color: var(--profit);">{{ $day->wins }}</td>
                            <td style="color: var(--loss);">{{ $day->losses }}</td>
                            <td>{{ number_format((float)$day->win_rate, 1) }}%</td>
                            <td style="color: {{ (float)$day->net_pnl >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-weight: 600;">
                                {{ (float)$day->net_pnl >= 0 ? '+' : '' }}${{ number_format((float)$day->net_pnl, 2) }}
                            </td>
                            <td style="color: {{ (float)$day->cumulative_pnl >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-weight: 600;">
                                {{ (float)$day->cumulative_pnl >= 0 ? '+' : '' }}${{ number_format((float)$day->cumulative_pnl, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ptx-empty-state">
                <i class="bi bi-calendar-week d-block"></i>
                <p>No trading data for the last 7 days.</p>
            </div>
        @endif
    </div>
</div>

{{-- Reset Balance Modal --}}
<div class="modal fade" id="resetBalanceModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #1a1d2e; border: 1px solid rgba(0,240,255,0.2); box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); background: linear-gradient(135deg, rgba(0,240,255,0.05) 0%, transparent 100%);">
                <h5 class="modal-title" style="color: #ffffff; font-weight: 600;">
                    <i class="bi bi-arrow-counterclockwise me-2" style="color: var(--accent);"></i>
                    Reset Balance
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('balance.reset') }}" method="POST">
                @csrf
                <div class="modal-body" style="padding: 1.5rem;">
                    <p style="color: #b8b8d1; margin-bottom: 1.25rem;">
                        Enter your desired starting balance. All equity history and trade data will be preserved, but balance tracking will restart from this amount.
                    </p>
                    @if(($openTradeCount ?? 0) > 0)
                        <div class="alert mb-3" style="background: rgba(255,193,7,0.12); border: 1px solid rgba(255,193,7,0.35); color: #ffd56a; border-radius: 8px;">
                            <i class="bi bi-lock-fill me-2"></i>
                            Reset is unavailable while you have {{ $openTradeCount }} open/pending trade(s). Turn off simulation and wait for resolution first.
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="reset_amount" class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 0.5rem;">
                            Reset Amount (USD)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #12141f; border: 1px solid rgba(255,255,255,0.1); color: var(--accent); border-right: none;">$</span>
                            <input
                                type="number"
                                class="form-control"
                                id="reset_amount"
                                name="amount"
                                value="100"
                                min="1"
                                max="1000000"
                                step="0.01"
                                required
                                {{ ($openTradeCount ?? 0) > 0 ? 'disabled' : '' }}
                                style="background: #12141f; border: 1px solid rgba(255,255,255,0.1); color: #ffffff; font-size: 1.1rem; font-weight: 500;"
                            >
                        </div>
                        <small style="color: #8888a0; display: block; margin-top: 0.5rem;">
                            Recommended: $100 - $10,000
                        </small>
                    </div>
                    <div class="alert mb-0" style="background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); color: #ff4757; border-radius: 8px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> All balance snapshots and daily summaries will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); padding: 1rem 1.5rem; background: rgba(0,0,0,0.2);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 0.5rem 1.25rem;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1.25rem; font-weight: 500;" {{ ($openTradeCount ?? 0) > 0 ? 'disabled' : '' }}>
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Balance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showResetModal() {
    const modal = new bootstrap.Modal(document.getElementById('resetBalanceModal'));
    modal.show();
}

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
            fill: true,
            tension: 0.3,
            pointRadius: 2,
            pointHoverRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#8888a0', font: { size: 10 } }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#8888a0', callback: v => '$' + v }
            }
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
            data: {!! json_encode($dailyPnl->pluck('net_pnl')) !!},
            backgroundColor: {!! json_encode($dailyPnl->pluck('net_pnl')->map(fn($v) => (float)$v >= 0 ? 'rgba(0,230,118,0.6)' : 'rgba(255,71,87,0.6)')) !!},
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#8888a0', font: { size: 10 } }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#8888a0', callback: v => '$' + v }
            }
        }
    }
});
@endif
</script>
@endpush
