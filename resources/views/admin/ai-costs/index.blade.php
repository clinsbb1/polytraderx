@extends('layouts.super-admin')

@section('title', 'AI Cost Monitoring')

@section('content')
{{-- Summary Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="text-muted small">Total Spend (All Time)</div>
                    <div class="fs-4 fw-bold">${{ number_format((float)$totalSpend, 4) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-calendar-day"></i></div>
                <div>
                    <div class="text-muted small">Today's Spend</div>
                    <div class="fs-4 fw-bold">${{ number_format((float)$todaySpend, 4) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-month"></i></div>
                <div>
                    <div class="text-muted small">This Month</div>
                    <div class="fs-4 fw-bold">${{ number_format((float)$monthSpend, 4) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Daily Cost Chart --}}
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Daily AI Spend (Last 30 Days)</h6></div>
    <div class="card-body">
        <canvas id="dailyCostChart" height="80"></canvas>
    </div>
</div>

{{-- Per-User Cost Table --}}
<div class="card">
    <div class="card-header"><h6 class="mb-0">Per-User AI Costs</h6></div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Account ID</th>
                    <th>Plan</th>
                    <th class="text-end">Total Calls</th>
                    <th class="text-end">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perUserCosts as $row)
                <tr>
                    <td>
                        @if($row['user'])
                            <a href="/admin/users/{{ $row['user']->id }}">{{ $row['user']->name }}</a>
                        @else
                            <span class="text-muted">Deleted User</span>
                        @endif
                    </td>
                    <td><code class="small">{{ $row['user']?->account_id ?? '—' }}</code></td>
                    <td>{{ $row['user']?->subscription_plan ?? '—' }}</td>
                    <td class="text-end">{{ number_format($row['total_calls']) }}</td>
                    <td class="text-end fw-bold">${{ number_format((float)$row['total_cost'], 4) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No AI costs recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const dailyData = @json($dailyCosts);
    new Chart(document.getElementById('dailyCostChart'), {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Daily Cost ($)',
                data: dailyData.map(d => parseFloat(d.daily_cost)),
                backgroundColor: 'rgba(99, 102, 241, 0.5)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '$' + v.toFixed(4) } }
            }
        }
    });
</script>
@endsection
