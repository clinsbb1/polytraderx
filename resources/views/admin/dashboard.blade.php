@extends('layouts.super-admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                <div><div class="text-muted small">Total Users</div><div class="fw-bold fs-4">{{ number_format($stats['total_users']) }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-credit-card"></i></div>
                <div><div class="text-muted small">Paid Subscriptions</div><div class="fw-bold fs-4">{{ number_format($stats['paid_subscriptions']) }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-currency-dollar"></i></div>
                <div><div class="text-muted small">Total Revenue</div><div class="fw-bold fs-4">${{ number_format($stats['total_revenue'], 2) }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-activity"></i></div>
                <div><div class="text-muted small">Active Bots</div><div class="fw-bold fs-4">{{ number_format($stats['active_bots']) }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body text-center"><div class="text-muted small">Total Trades</div><div class="fw-bold fs-3">{{ number_format($stats['total_trades']) }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body text-center"><div class="text-muted small">New Users Today</div><div class="fw-bold fs-3">{{ $stats['users_today'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body text-center"><div class="text-muted small">Trades Today</div><div class="fw-bold fs-3">{{ $stats['trades_today'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body text-center"><div class="text-muted small">Pending Payments</div><div class="fw-bold fs-3">{{ $stats['pending_payments'] }}</div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">AI Budget Guardrail</h6>
        <span class="badge bg-{{ $aiBudgetPaused ? 'danger' : 'success' }}">{{ $aiBudgetPaused ? 'PAUSED' : 'ACTIVE' }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <div class="text-muted small">Monthly Spend</div>
                    <div class="fw-bold fs-5">${{ number_format((float) $monthlyAiSpend, 4) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <div class="text-muted small">Monthly Budget</div>
                    <div class="fw-bold fs-5">${{ number_format((float) $aiMonthlyBudget, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <div class="text-muted small">Budget Remaining</div>
                    <div class="fw-bold fs-5">${{ number_format((float) $aiBudgetRemaining, 4) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3">
                    <div class="text-muted small">Used</div>
                    <div class="fw-bold fs-5">{{ number_format((float) $aiBudgetUsedPct, 1) }}%</div>
                </div>
            </div>
        </div>

        <div class="progress mb-3" role="progressbar" aria-valuenow="{{ number_format((float) $aiBudgetUsedPct, 1) }}" aria-valuemin="0" aria-valuemax="100" style="height: 10px;">
            <div class="progress-bar {{ $aiBudgetUsedPct >= 100 ? 'bg-danger' : ($aiBudgetUsedPct >= 80 ? 'bg-warning' : 'bg-success') }}" style="width: {{ number_format((float) $aiBudgetUsedPct, 2) }}%"></div>
        </div>

        <div class="small text-muted mb-2">Top users by monthly AI token usage</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Account ID</th>
                        <th class="text-end">Tokens</th>
                        <th class="text-end">Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aiTopUsers as $usage)
                        <tr>
                            <td>{{ $usage->user?->name ?? 'Deleted User' }}</td>
                            <td><code>{{ $usage->user?->account_id ?? '—' }}</code></td>
                            <td class="text-end">{{ number_format((int) $usage->total_tokens) }}</td>
                            <td class="text-end">${{ number_format((float) $usage->total_cost_usd, 4) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No AI usage this month yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-header"><h6 class="mb-0">Signups (30d)</h6></div><div class="card-body"><canvas id="signupsChart" height="200"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><h6 class="mb-0">Revenue (30d)</h6></div><div class="card-body"><canvas id="revenueChart" height="200"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><h6 class="mb-0">Trades (30d)</h6></div><div class="card-body"><canvas id="tradesChart" height="200"></canvas></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">System Health</h6>
        <div class="d-flex align-items-center gap-2">
            @php $publicDataActive = (($health['services']['public_data_mode'] ?? 'disabled') === 'active'); @endphp
            <span class="badge bg-{{ $publicDataActive ? 'success' : 'warning' }}">
                Public Data Mode: {{ $publicDataActive ? 'ACTIVE' : 'DISABLED' }}
            </span>
            <a href="/admin/settings/diagnostics" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-activity me-1"></i>Run Diagnostics
            </a>
            <span class="badge bg-{{ $health['status'] === 'ok' ? 'success' : 'warning' }}">{{ strtoupper($health['status']) }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($health['services'] as $service => $status)
                @php
                    $badge = match($status) {
                        'ok', 'configured' => 'success',
                        'degraded' => 'warning',
                        default => 'secondary',
                    };
                @endphp
                <div class="col-md-4">
                    <div class="d-flex justify-content-between border rounded p-2">
                        <span class="small text-muted">{{ ucwords(str_replace('_', ' ', $service)) }}</span>
                        <span class="badge bg-{{ $badge }}">{{ strtoupper($status) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="small text-muted mt-2">Checked {{ $health['checked_at']->diffForHumans() }}</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Recent Signups</h6><a href="/admin/users" class="small">View All</a></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Name</th><th>Plan</th><th>Joined</th></tr></thead>
                    <tbody>
                        @foreach($recentUsers as $u)
                        <tr>
                            <td><a href="/admin/users/{{ $u->id }}">{{ $u->name }}</a></td>
                            <td><span class="badge bg-secondary">{{ $u->subscription_plan }}</span></td>
                            <td class="small text-muted">{{ $u->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Recent Payments</h6><a href="/admin/payments" class="small">View All</a></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentPayments as $p)
                        <tr>
                            <td>{{ $p->user->name ?? 'N/A' }}</td>
                            <td>${{ number_format((float)$p->amount_usd, 2) }}</td>
                            <td><span class="badge bg-{{ $p->status === 'finished' ? 'success' : ($p->status === 'pending' ? 'warning' : 'secondary') }}">{{ $p->status }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No payments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Recent Trades</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Asset</th><th>P&L</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentTrades as $t)
                        <tr>
                            <td class="small">{{ $t->user->account_id ?? 'N/A' }}</td>
                            <td>{{ $t->asset }}</td>
                            <td class="{{ (float)($t->pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ $t->pnl !== null ? '$' . number_format((float)$t->pnl, 2) : '—' }}</td>
                            <td><span class="badge bg-{{ $t->status === 'won' ? 'success' : ($t->status === 'lost' ? 'danger' : ($t->status === 'open' ? 'primary' : 'secondary')) }}">{{ $t->status }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">No trades yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
new Chart(document.getElementById('signupsChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($signupsPerDay->pluck('date')) !!},
        datasets: [{ label: 'Signups', data: {!! json_encode($signupsPerDay->pluck('count')) !!}, backgroundColor: 'rgba(99,102,241,0.6)', borderRadius: 4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($revenuePerDay->pluck('date')) !!},
        datasets: [{ label: 'Revenue', data: {!! json_encode($revenuePerDay->pluck('revenue')) !!}, backgroundColor: 'rgba(34,197,94,0.6)', borderRadius: 4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v } } } }
});
new Chart(document.getElementById('tradesChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($tradesPerDay->pluck('date')) !!},
        datasets: [{ label: 'Trades', data: {!! json_encode($tradesPerDay->pluck('count')) !!}, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
@endsection
