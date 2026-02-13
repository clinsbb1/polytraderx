@extends('layouts.admin')

@section('title', 'AI Costs')

@section('content')
{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="ptx-stat-card">
            <div class="ptx-stat-label">Today</div>
            <div class="ptx-stat-value">${{ number_format($todayCost, 4) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ptx-stat-card">
            <div class="ptx-stat-label">This Month</div>
            <div class="ptx-stat-value">${{ number_format($monthlyCost, 4) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ptx-stat-card">
            <div class="ptx-stat-label">Projected Monthly</div>
            <div class="ptx-stat-value">${{ number_format($projectedCost, 4) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ptx-stat-card">
            <div class="ptx-stat-label">All Time</div>
            <div class="ptx-stat-value">${{ number_format($totalCost, 4) }}</div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-3 mb-4">
    {{-- Tier Breakdown Doughnut --}}
    <div class="col-md-4">
        <div class="ptx-card h-100">
            <div class="ptx-card-header">
                <h5 class="mb-0">Cost by Tier</h5>
            </div>
            <div class="ptx-card-body d-flex align-items-center justify-content-center">
                @if($tierBreakdown->isEmpty())
                    <p style="color: var(--text-secondary);">No data yet.</p>
                @else
                    <canvas id="tierChart" style="max-height: 250px;"></canvas>
                @endif
            </div>
        </div>
    </div>

    {{-- Daily Spend Bar --}}
    <div class="col-md-8">
        <div class="ptx-card h-100">
            <div class="ptx-card-header">
                <h5 class="mb-0">Daily Spend (30d)</h5>
            </div>
            <div class="ptx-card-body">
                @if($dailySpend->isEmpty())
                    <p style="color: var(--text-secondary);">No data yet.</p>
                @else
                    <canvas id="dailyChart" height="120"></canvas>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Decisions Table --}}
<div class="ptx-card">
    <div class="ptx-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent AI Decisions</h5>
        <span style="color: var(--text-secondary); font-size: 0.85rem;">{{ $decisions->total() }} total</span>
    </div>
    <div class="ptx-card-body p-0">
        @if($decisions->count() > 0)
            <table class="ptx-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Tier</th>
                        <th>Model</th>
                        <th>Type</th>
                        <th class="text-end">Tokens</th>
                        <th class="text-end">Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($decisions as $d)
                        <tr>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $d->created_at->format('M d, H:i') }}</td>
                            <td>
                                @php
                                    $tierBadge = match($d->tier) {
                                        'reflexes' => 'success',
                                        'muscles' => 'warning',
                                        'brain' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="ptx-badge ptx-badge-{{ $tierBadge }}">{{ ucfirst($d->tier) }}</span>
                            </td>
                            <td style="font-size: 0.85rem;">{{ $d->model_used ?? '-' }}</td>
                            <td style="font-size: 0.85rem;">{{ $d->decision_type ?? '-' }}</td>
                            <td class="text-end" style="font-size: 0.85rem;">{{ number_format(($d->tokens_input ?? 0) + ($d->tokens_output ?? 0)) }}</td>
                            <td class="text-end" style="font-weight: 600;">${{ number_format((float) $d->cost_usd, 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-3">{{ $decisions->links() }}</div>
        @else
            <div class="ptx-empty-state">
                <i class="bi bi-cash-coin d-block"></i>
                <p>No AI decisions recorded yet. Costs will appear here once the bot runs AI analysis.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($tierBreakdown->isNotEmpty())
    const tierData = @json($tierBreakdown);
    const tierColors = { reflexes: '#00e676', muscles: '#ffab00', brain: '#ff4757' };
    new Chart(document.getElementById('tierChart'), {
        type: 'doughnut',
        data: {
            labels: tierData.map(t => t.tier.charAt(0).toUpperCase() + t.tier.slice(1)),
            datasets: [{
                data: tierData.map(t => parseFloat(t.total_cost)),
                backgroundColor: tierData.map(t => tierColors[t.tier] || '#8888a0'),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#c0c0d0', padding: 12 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ': $' + ctx.parsed.toFixed(4) + ' (' + tierData[ctx.dataIndex].call_count + ' calls)';
                        }
                    }
                }
            },
            cutout: '60%',
        }
    });
@endif

@if($dailySpend->isNotEmpty())
    const dailyData = @json($dailySpend);
    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Daily Spend ($)',
                data: dailyData.map(d => parseFloat(d.daily_cost)),
                backgroundColor: 'rgba(0, 240, 255, 0.3)',
                borderColor: '#00f0ff',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return '$' + ctx.parsed.y.toFixed(4); }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#8888a0', callback: v => '$' + v.toFixed(4) }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#8888a0', maxRotation: 45, font: { size: 10 } }
                }
            }
        }
    });
@endif
</script>
@endpush
