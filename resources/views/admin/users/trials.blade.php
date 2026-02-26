@extends('layouts.super-admin')

@section('title', 'Pro Trials')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0" style="font-family: var(--font-display);">Pro Trials</h4>
    <div>
        <a href="{{ url('/admin/users') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-people me-1"></i> All Users
        </a>
    </div>
</div>

{{-- Stats strip --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center">
            <div class="stat-label">Total Trials</div>
            <div class="stat-value">{{ $users->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center">
            <div class="stat-label">Active Now</div>
            <div class="stat-value text-accent">{{ $activeCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center">
            <div class="stat-label">Converted</div>
            <div class="stat-value" style="color: #17a2b8;">{{ $convertedCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center">
            <div class="stat-label">Expired</div>
            <div class="stat-value">{{ $expiredCount }}</div>
        </div>
    </div>
</div>

<div class="ptx-card">
    <div class="ptx-card-body p-3">
        @if($users->isEmpty())
            <div class="p-4 text-center" style="color: var(--text-secondary);">
                No users have started a Pro trial yet.
            </div>
        @else
        <table id="trialsTable" class="w-100" style="font-size: 0.88rem;">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Trial Started</th>
                    <th>Trial Ends</th>
                    <th>Status</th>
                    <th>Trades</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                @php
                    $trialEnd = $user->pro_trial_used_at ? $user->pro_trial_used_at->copy()->addDays(3) : null;
                    $isActive = $user->billing_interval === 'trial'
                        && $user->subscription_ends_at
                        && $user->subscription_ends_at->isFuture();
                    $isConverted = !$isActive
                        && in_array($user->subscription_plan, ['pro', 'advanced', 'lifetime'])
                        && ($user->is_lifetime || ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()))
                        && $user->billing_interval !== 'trial';
                    $statusLabel = $isActive ? 'Active' : ($isConverted ? 'Converted' : 'Expired');
                @endphp
                <tr>
                    <td>
                        <a href="{{ url('/admin/users/' . $user->id) }}" style="color: var(--accent); text-decoration: none; font-weight: 500;">
                            {{ $user->name }}
                        </a>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ $user->email }}</div>
                    </td>
                    <td data-order="{{ $user->pro_trial_used_at?->timestamp }}">
                        {{ $user->pro_trial_used_at?->format('M j, Y H:i') ?? '—' }}
                    </td>
                    <td data-order="{{ $trialEnd?->timestamp }}">
                        @if($trialEnd)
                            {{ $trialEnd->format('M j, Y H:i') }}
                            @if($isActive)
                                <div style="font-size: 0.75rem; color: var(--profit);">
                                    {{ ceil(now()->diffInHours($user->subscription_ends_at, false) / 24) }}d left
                                </div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td data-order="{{ $statusLabel }}">
                        @if($isActive)
                            <span class="ptx-badge ptx-badge-success">Active</span>
                        @elseif($isConverted)
                            <span class="ptx-badge ptx-badge-info">Converted</span>
                        @else
                            <span class="ptx-badge ptx-badge-secondary">Expired</span>
                        @endif
                    </td>
                    <td>{{ $user->trial_trades_count ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<style>
    #trialsTable_wrapper .dataTables_filter input,
    #trialsTable_wrapper .dataTables_length select {
        background: var(--bg-input, #1a1a2e);
        border: 1px solid var(--border-subtle, rgba(255,255,255,0.1));
        color: var(--text-primary, #fff);
        border-radius: 6px;
        padding: 4px 10px;
    }
    #trialsTable_wrapper .dataTables_info,
    #trialsTable_wrapper .dataTables_filter label,
    #trialsTable_wrapper .dataTables_length label {
        color: var(--text-secondary, rgba(255,255,255,0.6));
        font-size: 0.83rem;
    }
    #trialsTable thead th {
        border-bottom: 1px solid var(--border-subtle, rgba(255,255,255,0.1));
        color: var(--text-secondary, rgba(255,255,255,0.6));
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 10px 12px;
        white-space: nowrap;
    }
    #trialsTable tbody td {
        border-bottom: 1px solid var(--border-subtle, rgba(255,255,255,0.06));
        padding: 10px 12px;
        vertical-align: middle;
        color: var(--text-primary, #fff);
    }
    #trialsTable tbody tr:hover td {
        background: rgba(255,255,255,0.03);
    }
    #trialsTable_wrapper .page-link {
        background: var(--bg-card-solid, #13131f);
        border-color: var(--border-subtle, rgba(255,255,255,0.1));
        color: var(--text-primary, #fff);
    }
    #trialsTable_wrapper .page-item.active .page-link {
        background: var(--accent, #00f0ff);
        border-color: var(--accent, #00f0ff);
        color: #000;
    }
</style>
<script>
$(function() {
    $('#trialsTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ per page',
            info: 'Showing _START_–_END_ of _TOTAL_ trials',
            emptyTable: 'No trial users found.',
            zeroRecords: 'No matching trials found.',
        },
        columnDefs: [
            { orderable: false, targets: [] }
        ],
    });
});
</script>
@endsection
