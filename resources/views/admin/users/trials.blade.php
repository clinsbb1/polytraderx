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
            <div class="stat-value">{{ $users->total() }}</div>
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
            <div class="stat-value" style="color: var(--info, #17a2b8);">{{ $convertedCount }}</div>
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
    <div class="ptx-card-header">
        <h5 class="mb-0">Trial Users</h5>
    </div>
    <div class="ptx-card-body p-0">
        @if($users->isEmpty())
            <div class="p-4 text-center" style="color: var(--text-secondary);">
                No users have started a Pro trial yet.
            </div>
        @else
        <div class="table-responsive">
            <table class="ptx-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Trial Started</th>
                        <th>Trial Ends</th>
                        <th>Status</th>
                        <th>Trades (during trial)</th>
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
                            <a href="{{ url('/admin/users/' . $user->id) }}" style="color: var(--accent); text-decoration: none;">
                                {{ $user->name }}
                            </a>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">{{ $user->email }}</div>
                        </td>
                        <td style="font-size: 0.88rem;">
                            {{ $user->pro_trial_used_at ? $user->pro_trial_used_at->format('M j, Y H:i') : '—' }}
                        </td>
                        <td style="font-size: 0.88rem;">
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
                        <td>
                            @if($isActive)
                                <span class="ptx-badge ptx-badge-success">Active</span>
                            @elseif($isConverted)
                                <span class="ptx-badge ptx-badge-info">Converted</span>
                            @else
                                <span class="ptx-badge ptx-badge-secondary">Expired</span>
                            @endif
                        </td>
                        <td style="font-size: 0.88rem;">
                            {{ $user->trial_trades_count ?? 0 }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-3">
                {{ $users->links() }}
            </div>
        @endif
        @endif
    </div>
</div>
@endsection
