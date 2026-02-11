@extends('layouts.admin')

@section('title', 'Subscription')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Subscription</h1>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- Current Plan -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Current Plan</h5>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Plan:</strong> {{ $currentPlan ? $currentPlan->name : ucfirst(str_replace('_', ' ', $user->subscription_plan ?? 'Free Trial')) }}</p>
                @if($user->subscription_plan === 'free_trial' && $user->trial_ends_at)
                    <p class="mb-1"><strong>Trial Ends:</strong> {{ $user->trial_ends_at->format('M j, Y') }}</p>
                    <p class="mb-0">
                        @if($user->isTrialExpired())
                            <span class="badge bg-danger">Trial Expired</span>
                        @else
                            <span class="badge bg-info">{{ $user->daysLeftInTrial() }} days remaining</span>
                        @endif
                    </p>
                @elseif($user->subscription_ends_at)
                    <p class="mb-1"><strong>Renews:</strong> {{ $user->subscription_ends_at->format('M j, Y') }}</p>
                    <span class="badge bg-success">Active</span>
                @endif
            </div>
            @if($currentPlan)
            <div class="col-md-6">
                <p class="mb-1"><strong>Max Daily Trades:</strong> {{ $currentPlan->max_daily_trades ?: 'Unlimited' }}</p>
                <p class="mb-1"><strong>Max Concurrent:</strong> {{ $currentPlan->max_concurrent_positions ?: 'Unlimited' }}</p>
                <p class="mb-1"><strong>AI Muscles:</strong> {{ $currentPlan->has_ai_muscles ? 'Yes' : 'No' }}</p>
                <p class="mb-0"><strong>AI Brain:</strong> {{ $currentPlan->has_ai_brain ? 'Yes' : 'No' }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Available Plans -->
<h5 class="mb-3">Upgrade Your Plan</h5>
<div class="row g-4">
    @foreach($plans as $plan)
    <div class="col-md-4">
        <div class="card h-100 {{ $user->subscription_plan === $plan->slug ? 'border-primary' : '' }}">
            <div class="card-body text-center">
                @if($user->subscription_plan === $plan->slug)
                    <span class="badge bg-primary mb-2">Current Plan</span>
                @endif
                <h5 class="card-title">{{ $plan->name }}</h5>
                <div class="my-3">
                    @if((float)$plan->price_usd === 0.0)
                        <span class="display-6 fw-bold">Free</span>
                    @else
                        <span class="display-6 fw-bold">${{ number_format((float)$plan->price_usd, 0) }}</span>
                        <span class="text-muted">/{{ $plan->billing_period }}</span>
                    @endif
                </div>
                <ul class="list-unstyled text-start small">
                    <li class="mb-1"><i class="bi bi-check text-success"></i> {{ $plan->max_daily_trades ?: 'Unlimited' }} trades/day</li>
                    <li class="mb-1"><i class="bi bi-check text-success"></i> {{ $plan->max_concurrent_positions ?: 'Unlimited' }} positions</li>
                    <li class="mb-1"><i class="bi {{ $plan->has_ai_muscles ? 'bi-check text-success' : 'bi-x text-muted' }}"></i> AI Muscles</li>
                    <li class="mb-1"><i class="bi {{ $plan->has_ai_brain ? 'bi-check text-success' : 'bi-x text-muted' }}"></i> AI Brain</li>
                </ul>
                @if($user->subscription_plan !== $plan->slug && (float)$plan->price_usd > 0)
                    <form method="POST" action="/subscription/checkout">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn btn-primary w-100">Pay with Crypto</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
