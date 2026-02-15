@extends('layouts.admin')

@section('title', 'Subscription')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">Subscription</h4>

@if(session('error'))
    <div class="ptx-alert ptx-alert-danger">
        <i class="bi bi-x-circle-fill"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<!-- Current Plan -->
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>Current Plan</h5>
    </div>
    <div class="ptx-card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><span class="ptx-label d-inline">Plan:</span> <strong>{{ $currentPlan ? $currentPlan->name : ucfirst(str_replace('_', ' ', $user->subscription_plan ?? 'Free')) }}</strong></p>
                @if($user->is_lifetime)
                    <p class="mb-0"><span class="ptx-badge ptx-badge-success">Lifetime Access</span></p>
                @elseif($user->subscription_ends_at)
                    <p class="mb-2"><span class="ptx-label d-inline">Renews:</span> <strong>{{ $user->subscription_ends_at->format('M j, Y') }}</strong></p>
                    <span class="ptx-badge ptx-badge-success">Active</span>
                @endif
            </div>
            @if($currentPlan)
            <div class="col-md-6">
                <p class="mb-2"><span class="ptx-label d-inline">Max Daily Trades:</span> <strong>{{ $currentPlan->max_signals_per_day ?: 'Unlimited' }}</strong></p>
                <p class="mb-2"><span class="ptx-label d-inline">Max Concurrent:</span> <strong>{{ $currentPlan->max_concurrent_positions ?: 'Unlimited' }}</strong></p>
                <p class="mb-2"><span class="ptx-label d-inline">AI Muscles:</span> <strong>{{ $currentPlan->ai_muscles_enabled ? 'Yes' : 'No' }}</strong></p>
                <p class="mb-0"><span class="ptx-label d-inline">AI Brain:</span> <strong>{{ $currentPlan->ai_brain_enabled ? 'Yes' : 'No' }}</strong></p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Available Plans -->
<h5 class="mb-3" style="font-family: var(--font-display);">Upgrade Your Plan</h5>
<div class="row g-4">
    @foreach($plans as $plan)
    <div class="col-md-4">
        <div class="ptx-plan-card {{ $user->subscription_plan === $plan->slug ? 'current' : '' }}">
            @if($user->subscription_plan === $plan->slug)
                <div class="plan-badge">Current Plan</div>
            @endif
            <div class="plan-name">{{ $plan->name }}</div>
            <div class="my-3">
                @if((float)$plan->price_usd === 0.0)
                    <span class="plan-price">Free</span>
                @else
                    <span class="plan-price">${{ number_format((float)$plan->price_usd, 0) }}</span>
                    <div class="plan-period">/{{ $plan->billing_period }}</div>
                @endif
            </div>
            <ul class="plan-features">
                <li><i class="bi bi-check-circle-fill check"></i> {{ $plan->max_signals_per_day ?: 'Unlimited' }} signals/day</li>
                <li><i class="bi bi-check-circle-fill check"></i> {{ $plan->max_concurrent_positions ?: 'Unlimited' }} positions</li>
                <li><i class="bi {{ $plan->ai_muscles_enabled ? 'bi-check-circle-fill check' : 'bi-x-circle cross' }}"></i> AI Muscles</li>
                <li><i class="bi {{ $plan->ai_brain_enabled ? 'bi-check-circle-fill check' : 'bi-x-circle cross' }}"></i> AI Brain</li>
            </ul>
            @if($user->subscription_plan !== $plan->slug && (float)$plan->price_usd > 0)
                <form method="POST" action="/subscription/checkout">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button type="submit" class="btn-ptx-success w-100">Pay with Crypto</button>
                </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
