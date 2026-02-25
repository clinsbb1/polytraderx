@extends('layouts.super-admin')

@section('title', 'Manage Users')

@section('content')
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Plan</label>
                <select name="plan" class="form-select">
                    <option value="">All Plans</option>
                    @foreach($planOptions as $planOption)
                        @continue(!$planOption->is_active && request('plan') !== $planOption->slug)
                        <option value="{{ $planOption->slug }}" {{ request('plan') === $planOption->slug ? 'selected' : '' }}>
                            {{ $planOption->name }}{{ !$planOption->is_active ? ' (inactive)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="active" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
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

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Account ID</th>
                        <th>Plan</th>
                        <th>Telegram</th>
                        <th>Google</th>
                        <th>Status</th>
                        <th class="text-center">Trades</th>
                        <th>Registered</th>
                        <th>Last Active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <a href="/admin/users/{{ $user->id }}" class="fw-semibold text-decoration-none">
                                {{ $user->name }}
                            </a>
                        </td>
                        <td class="small text-muted">{{ $user->email }}</td>
                        <td><code class="small">{{ $user->account_id }}</code></td>
                        <td>
                            @php
                                $planColors = ['pro' => 'primary', 'advanced' => 'warning', 'lifetime' => 'success'];
                                $planLabel = $user->subscription_plan ?? 'n/a';
                            @endphp
                            <span class="badge bg-{{ $planColors[$planLabel] ?? 'secondary' }}">{{ str_replace('_', ' ', ucfirst($planLabel)) }}</span>
                        </td>
                        <td>
                            @if($user->telegram_chat_id)
                                <span class="badge bg-success">Linked</span>
                                @if($user->telegram_username)
                                    <div class="small text-muted mt-1">{{ '@' . $user->telegram_username }}</div>
                                @endif
                            @else
                                <span class="badge bg-secondary">Not Linked</span>
                            @endif
                        </td>
                        <td>
                            @if($user->google_id)
                                <span class="badge bg-success">Linked</span>
                            @else
                                <span class="badge bg-secondary">Not Linked</span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $user->trades_count ?? 0 }}</td>
                        <td class="small text-muted">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="small text-muted">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->diffForHumans() }}
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-people fs-3 d-block mb-2"></i>
                            No users found matching your criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
@endsection
