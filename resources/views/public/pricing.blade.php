@extends('layouts.public')

@section('title', 'Pricing — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="text-center fw-bold mb-2">Pricing</h1>
        <p class="text-center text-muted mb-5">Choose the plan that fits your trading needs. Pay with crypto.</p>

        <div class="row g-4 justify-content-center">
            @forelse($plans as $plan)
            <div class="col-md-4">
                <div class="pricing-card p-4 text-center {{ $plan->slug === 'basic' ? 'featured' : '' }}">
                    @if($plan->slug === 'basic')
                        <span class="pricing-badge">Most Popular</span>
                    @endif
                    <h4 class="fw-bold mt-2">{{ $plan->name }}</h4>
                    <div class="my-3">
                        @if((float)$plan->price_usd === 0.0)
                            <span class="display-5 fw-bold">Free</span>
                            <br><span class="text-muted">{{ $plan->trial_days }}-day trial</span>
                        @else
                            <span class="display-5 fw-bold">${{ number_format((float)$plan->price_usd, 0) }}</span>
                            <span class="text-muted">/{{ $plan->billing_period }}</span>
                        @endif
                    </div>
                    <ul class="list-unstyled text-start mb-4">
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_daily_trades ?: 'Unlimited' }} trades/day</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_concurrent_positions ?: 'Unlimited' }} concurrent positions</li>
                        <li class="mb-2"><i class="bi {{ $plan->has_ai_muscles ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>AI Muscles (Haiku scoring)</li>
                        <li class="mb-2"><i class="bi {{ $plan->has_ai_brain ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>AI Brain (Sonnet forensics)</li>
                        @if($plan->features_json)
                            @foreach($plan->features_json as $feature)
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                    @auth
                        <a href="/subscription" class="btn {{ $plan->slug === 'basic' ? 'btn-ptx' : 'btn-ptx-outline' }} w-100">
                            {{ auth()->user()->subscription_plan === $plan->slug ? 'Current Plan' : 'Upgrade' }}
                        </a>
                    @else
                        <a href="/register" class="btn {{ $plan->slug === 'basic' ? 'btn-ptx' : 'btn-ptx-outline' }} w-100">Get Started</a>
                    @endauth
                </div>
            </div>
            @empty
            <div class="col-12 text-center">
                <p class="text-muted">Pricing plans coming soon.</p>
            </div>
            @endforelse
        </div>

        <div class="text-center mt-5">
            <p class="text-muted">All plans include: full dashboard, trade history, equity tracking, and Telegram notifications.</p>
            <p class="text-muted small">Prices in USD. Crypto payments accepted via NOWPayments.</p>
        </div>
    </div>
</section>
@endsection
