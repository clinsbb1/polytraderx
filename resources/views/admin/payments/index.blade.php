@extends('layouts.super-admin')

@section('title', 'Payments')

@section('content')
<!-- Revenue Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="text-muted small">Total Revenue</div>
                <div class="fw-bold fs-4">${{ number_format((float)$stats['total_revenue'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="text-muted small">This Month</div>
                <div class="fw-bold fs-4">${{ number_format((float)$stats['this_month'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="text-muted small">Pending</div>
                <div class="fw-bold fs-4">{{ $stats['pending_count'] }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['pending','confirming','confirmed','finished','failed','expired','refunded'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>ID</th><th>User</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->user->name ?? 'N/A' }}</td>
                    <td>{{ $payment->subscriptionPlan->name ?? 'N/A' }}</td>
                    <td>${{ number_format((float)$payment->amount_usd, 2) }}</td>
                    <td><span class="badge bg-{{ $payment->status === 'finished' ? 'success' : ($payment->status === 'pending' ? 'warning' : ($payment->status === 'failed' ? 'danger' : 'secondary')) }}">{{ $payment->status }}</span></td>
                    <td class="small text-muted">{{ $payment->created_at->format('M j, Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No payments found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $payments->links() }}</div>
@endsection
