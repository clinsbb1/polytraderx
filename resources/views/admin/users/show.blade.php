@extends('layouts.super-admin')

@section('title', 'User: ' . $user->name)

@section('content')
<div class="row g-4">
    <!-- Profile -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Profile</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td class="fw-semibold">ID</td><td>{{ $user->id }}</td></tr>
                    <tr><td class="fw-semibold">Name</td><td>{{ $user->name }}</td></tr>
                    <tr><td class="fw-semibold">Email</td><td>{{ $user->email }}</td></tr>
                    <tr><td class="fw-semibold">Timezone</td><td>{{ $user->timezone ?? 'Not set' }}</td></tr>
                    <tr><td class="fw-semibold">Google</td><td>{{ $user->google_id ? 'Linked' : 'No' }}</td></tr>
                    <tr><td class="fw-semibold">Joined</td><td>{{ $user->created_at->format('M j, Y H:i') }}</td></tr>
                    <tr><td class="fw-semibold">Onboarded</td><td>{{ $user->onboarding_completed ? 'Yes' : 'No' }}</td></tr>
                    <tr><td class="fw-semibold">Last Heartbeat</td><td>{{ $user->last_bot_heartbeat?->diffForHumans() ?? 'Never' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscription & Actions -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Subscription</h6></div>
            <div class="card-body">
                <p><strong>Plan:</strong> {{ $user->subscription_plan ?? 'free_trial' }}</p>
                @if($user->trial_ends_at)
                    <p><strong>Trial Ends:</strong> {{ $user->trial_ends_at->format('M j, Y') }}</p>
                @endif
                @if($user->subscription_ends_at)
                    <p><strong>Subscription Ends:</strong> {{ $user->subscription_ends_at->format('M j, Y') }}</p>
                @endif
                <p>
                    <strong>Status:</strong>
                    @if($user->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </p>

                <hr>

                <form method="POST" action="/admin/users/{{ $user->id }}/toggle-active" class="d-inline">
                    @csrf
                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>

                <form method="POST" action="/admin/users/{{ $user->id }}/change-plan" class="mt-3">
                    @csrf
                    <div class="input-group input-group-sm">
                        <select name="subscription_plan" class="form-select form-select-sm">
                            <option value="free_trial" {{ $user->subscription_plan === 'free_trial' ? 'selected' : '' }}>Free Trial</option>
                            <option value="basic" {{ $user->subscription_plan === 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="pro" {{ $user->subscription_plan === 'pro' ? 'selected' : '' }}>Pro</option>
                        </select>
                        <button class="btn btn-sm btn-primary">Change Plan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Credentials -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0">API Credentials</h6></div>
            <div class="card-body">
                @if($user->credential)
                    <p><i class="bi {{ $user->credential->hasPolymarketKeys() ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }}"></i> Polymarket</p>
                    <p><i class="bi {{ $user->credential->hasTelegramKeys() ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }}"></i> Telegram</p>
                    <p><i class="bi {{ $user->credential->hasAnthropicKey() ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }}"></i> Anthropic</p>
                @else
                    <p class="text-muted">No credentials configured</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Trade Stats -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Trade Statistics</h6></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col"><div class="fw-bold fs-4">{{ $tradeStats['total'] }}</div><div class="text-muted small">Total</div></div>
                    <div class="col"><div class="fw-bold fs-4 text-success">{{ $tradeStats['won'] }}</div><div class="text-muted small">Won</div></div>
                    <div class="col"><div class="fw-bold fs-4 text-danger">{{ $tradeStats['lost'] }}</div><div class="text-muted small">Lost</div></div>
                    <div class="col"><div class="fw-bold fs-4 text-info">{{ $tradeStats['open'] }}</div><div class="text-muted small">Open</div></div>
                    <div class="col"><div class="fw-bold fs-4 {{ (float)$tradeStats['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format((float)$tradeStats['total_pnl'], 2) }}</div><div class="text-muted small">Total P&L</div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
