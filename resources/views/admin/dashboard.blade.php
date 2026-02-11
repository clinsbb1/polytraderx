@extends('layouts.super-admin')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">Total Users</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['total_users']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-activity"></i></div>
                <div>
                    <div class="text-muted small">Active Bots</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['active_bots']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-arrow-left-right"></i></div>
                <div>
                    <div class="text-muted small">Total Trades</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['total_trades']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-currency-dollar"></i></div>
                <div>
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fw-bold fs-4">${{ number_format((float)$stats['total_revenue'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="text-muted small">New Users Today</div>
                <div class="fw-bold fs-3">{{ $stats['users_today'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="text-muted small">Trades Today</div>
                <div class="fw-bold fs-3">{{ $stats['trades_today'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="text-muted small">Pending Payments</div>
                <div class="fw-bold fs-3">{{ $stats['pending_payments'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Users -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0">Recent Users</h6>
                <a href="/admin/users" class="small">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Name</th><th>Plan</th><th>Joined</th></tr></thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                        <tr>
                            <td><a href="/admin/users/{{ $user->id }}">{{ $user->name }}</a></td>
                            <td><span class="badge bg-secondary">{{ $user->subscription_plan }}</span></td>
                            <td class="small text-muted">{{ $user->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0">Recent Payments</h6>
                <a href="/admin/payments" class="small">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->user->name ?? 'N/A' }}</td>
                            <td>${{ number_format((float)$payment->amount_usd, 2) }}</td>
                            <td><span class="badge bg-{{ $payment->status === 'finished' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'secondary') }}">{{ $payment->status }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No payments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
