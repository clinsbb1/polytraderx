@extends('layouts.super-admin')

@section('title', 'Pro Trials')

@section('extra-styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold">Pro Trials</h4>
    <a href="{{ url('/admin/users') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-people me-1"></i> All Users
    </a>
</div>

{{-- Stats strip --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Total Trials</div>
                <div class="fs-4 fw-bold">{{ $users->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-success">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Active Now</div>
                <div class="fs-4 fw-bold text-success">{{ $activeCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100 border-info">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Converted</div>
                <div class="fs-4 fw-bold text-info">{{ $convertedCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Expired</div>
                <div class="fs-4 fw-bold text-secondary">{{ $expiredCount }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-3">
        @if($users->isEmpty())
            <p class="text-muted text-center py-4 mb-0">No users have started a Pro trial yet.</p>
        @else
        <div class="table-responsive">
            <table id="trialsTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Trial Started</th>
                        <th>Trial Ends</th>
                        <th>Status</th>
                        <th class="text-center">Trades</th>
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
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ url('/admin/users/' . $user->id) }}" class="fw-semibold text-decoration-none">
                                {{ $user->name }}
                            </a>
                            <div class="text-muted" style="font-size:0.78rem;">{{ $user->email }}</div>
                        </td>
                        <td data-order="{{ $user->pro_trial_used_at?->timestamp ?? 0 }}" style="font-size:0.88rem;">
                            {{ $user->pro_trial_used_at?->format('M j, Y H:i') ?? '—' }}
                        </td>
                        <td data-order="{{ $trialEnd?->timestamp ?? 0 }}" style="font-size:0.88rem;">
                            @if($trialEnd)
                                {{ $trialEnd->format('M j, Y H:i') }}
                                @if($isActive)
                                    <div class="text-success" style="font-size:0.75rem;">
                                        {{ ceil(now()->diffInHours($user->subscription_ends_at, false) / 24) }}d left
                                    </div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($isActive)
                                <span class="badge bg-success">Active</span>
                            @elseif($isConverted)
                                <span class="badge bg-info text-dark">Converted</span>
                            @else
                                <span class="badge bg-secondary">Expired</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $user->trial_trades_count ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
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
    });
});
</script>
@endsection
