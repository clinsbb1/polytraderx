@extends('layouts.admin')

@section('title', 'Balance & Equity')

@section('content')
{{-- Row 1: Stat Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-4 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Current Balance</div>
            <div class="stat-value text-accent">${{ $latestSnapshot ? number_format((float)$latestSnapshot->balance_usdc, 2) : '0.00' }}</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Open Positions Value</div>
            <div class="stat-value text-accent">${{ $latestSnapshot ? number_format((float)$latestSnapshot->open_positions_value, 2) : '0.00' }}</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="ptx-stat-card">
            <div class="stat-label">Total Equity</div>
            <div class="stat-value text-accent">${{ $latestSnapshot ? number_format((float)$latestSnapshot->total_equity, 2) : '0.00' }}</div>
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
