@extends('layouts.super-admin')

@section('title', 'Payments')

@section('content')
<!-- Revenue Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fw-bold fs-4">${{ number_format((float)$stats['total_revenue'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div>
                    <div class="text-muted small">This Month</div>
                    <div class="fw-bold fs-4">${{ number_format((float)$stats['this_month'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending Payments</div>
                    <div class="fw-bold fs-4">{{ $stats['pending_count'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['pending','confirming','finished','failed','expired'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Amount USD</th>
                        <th>Status</th>
                        <th>NOWPayments ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $payment)
                    <tr data-bs-toggle="collapse" data-bs-target="#ipn-{{ $payment->id }}" role="button" class="cursor-pointer">
                        <td class="ps-3" style="width: 30px;">
                            <i class="bi bi-chevron-right small text-muted"></i>
                        </td>
                        <td class="small text-muted text-nowrap">{{ $payment->created_at->format('M j, Y H:i') }}</td>
                        <td>
                            @if($payment->user)
                                <a href="/admin/users/{{ $payment->user->id }}" class="text-decoration-none">
                                    {{ $payment->user->name }}
                                </a>
                                <div class="small text-muted"><code>{{ $payment->user->account_id }}</code></div>
                            @else
                                <span class="text-muted">Deleted User</span>
                            @endif
                        </td>
                        <td>{{ $payment->subscriptionPlan->name ?? 'N/A' }}</td>
                        <td class="fw-semibold">${{ number_format((float)$payment->amount_usd, 2) }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'finished' => 'success',
                                    'confirmed' => 'success',
                                    'confirming' => 'info',
                                    'pending' => 'warning',
                                    'failed' => 'danger',
                                    'expired' => 'dark',
                                    'refunded' => 'secondary',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$payment->status] ?? 'secondary' }}">{{ ucfirst($payment->status) }}</span>
                        </td>
                        <td class="small"><code>{{ $payment->nowpayments_id ?? '—' }}</code></td>
                    </tr>
                    <tr class="collapse" id="ipn-{{ $payment->id }}">
                        <td colspan="7" class="bg-light p-3">
                            <strong class="small d-block mb-2">IPN Data:</strong>
                            @if($payment->ipn_data)
                                <pre class="bg-white border rounded p-3 mb-0 small" style="max-height: 300px; overflow: auto;">{{ json_encode($payment->ipn_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="text-muted small">No IPN data received yet.</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-credit-card fs-3 d-block mb-2"></i>
                            No payments found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $payments->withQueryString()->links() }}</div>
@endsection

@section('extra-styles')
<style>
    [data-bs-toggle="collapse"] .bi-chevron-right {
        transition: transform 0.2s;
    }
    [data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-right {
        transform: rotate(90deg);
    }
    .cursor-pointer { cursor: pointer; }
</style>
@endsection
