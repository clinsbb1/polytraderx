@extends('layouts.super-admin')

@section('title', 'Manage Users')

@section('content')
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="plan" class="form-select">
                    <option value="">All Plans</option>
                    <option value="free_trial" {{ request('plan') === 'free_trial' ? 'selected' : '' }}>Free Trial</option>
                    <option value="basic" {{ request('plan') === 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="pro" {{ request('plan') === 'pro' ? 'selected' : '' }}>Pro</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="active" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Onboarded</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td><a href="/admin/users/{{ $user->id }}">{{ $user->name }}</a></td>
                    <td class="small">{{ $user->email }}</td>
                    <td><span class="badge bg-secondary">{{ $user->subscription_plan ?? 'trial' }}</span></td>
                    <td>
                        @if($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($user->onboarding_completed)
                            <i class="bi bi-check-circle text-success"></i>
                        @else
                            <i class="bi bi-clock text-muted"></i>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $user->created_at->format('M j, Y') }}</td>
                    <td>
                        <a href="/admin/users/{{ $user->id }}" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No users found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links() }}</div>
@endsection
