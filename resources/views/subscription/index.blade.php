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
@if($user->isSubscriptionActive())
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>Current Plan</h5>
    </div>
    <div class="ptx-card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><span class="ptx-label d-inline">Plan:</span> <strong>{{ $currentPlan ? $currentPlan->name : ucfirst(str_replace('_', ' ', $user->subscription_plan ?? 'No plan')) }}</strong></p>
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
@else
<div class="ptx-card mb-4">
    <div class="ptx-card-body">
        <p class="mb-0">No active plan — select one below to get started.</p>
    </div>
</div>
@endif

<!-- Available Plans -->
<h5 class="mb-3" style="font-family: var(--font-display);">{{ $user->isSubscriptionActive() ? 'Upgrade Your Plan' : 'Choose a Plan' }}</h5>
<div class="row g-4">
    @foreach($plans as $plan)
    @if($plan->slug === 'free' && !$freeModeEnabled) @continue @endif
    <div class="col-md-4">
        <div class="ptx-plan-card {{ $user->subscription_plan === $plan->slug ? 'current' : '' }}">
            @if($user->subscription_plan === $plan->slug)
                <div class="plan-badge">Current Plan</div>
            @endif
            <div class="plan-name">{{ $plan->name }}</div>
            <div class="my-3">
                @if((float)$plan->price_usd === 0.0)
                    @if($freeModeEnabled && $plan->slug === 'free')
                        <span class="plan-price">Free</span>
                    @else
                        <span class="plan-price">${{ number_format((float)$plan->price_usd, 0) }}</span>
                        <div class="plan-period">/{{ $plan->billing_period }}</div>
                    @endif
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

<!-- FAQ -->
<h5 class="mb-3 mt-5" style="font-family: var(--font-display);">Frequently Asked Questions</h5>
<div class="accordion ptx-accordion" id="subscriptionFaq">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq1">What cryptocurrencies do you accept?</button>
        </h2>
        <div id="sfaq1" class="accordion-collapse collapse" data-bs-parent="#subscriptionFaq">
            <div class="accordion-body">We accept BTC, ETH, USDC, USDT, SOL, and many other cryptocurrencies via NOWPayments. Over 100 cryptocurrencies are supported.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq2">Can I cancel anytime?</button>
        </h2>
        <div id="sfaq2" class="accordion-collapse collapse" data-bs-parent="#subscriptionFaq">
            <div class="accordion-body">Yes. You can cancel your subscription at any time. Your access continues until the end of the current billing period. No refunds for partial periods.</div>
        </div>
    </div>
    @if($freeModeEnabled)
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq3">How does the Free plan work?</button>
        </h2>
        <div id="sfaq3" class="accordion-collapse collapse" data-bs-parent="#subscriptionFaq">
            <div class="accordion-body">Sign up for free—no payment required, no credit card needed. You get immediate access to the simulator with 10 signals per day and basic features. All activity is simulated using real market data. Upgrade to a paid plan anytime for more signals, AI Brain analysis, and advanced features like Strategy Health Metrics and Telegram notifications.</div>
        </div>
    </div>
    @endif
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq4">Do I need a credit card?</button>
        </h2>
        <div id="sfaq4" class="accordion-collapse collapse" data-bs-parent="#subscriptionFaq">
            <div class="accordion-body">No. We only accept cryptocurrency payments. No credit card, bank account, or traditional payment method is needed. This ensures privacy and global accessibility.</div>
        </div>
    </div>
</div>
<p class="mt-3 mb-0" style="color: var(--text-secondary); font-size: 0.9rem;">All plans are billed in cryptocurrency via NOWPayments. Prices shown in USD equivalent.</p>
@endsection
