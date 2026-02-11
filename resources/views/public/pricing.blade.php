@extends('layouts.public')

@section('title', 'Pricing — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="text-center fw-bold mb-2">Simple, Transparent Pricing</h1>
        <p class="text-center text-muted mb-5">Choose the plan that fits your trading needs. All plans include a 7-day free trial. Pay with crypto.</p>

        <div class="row g-4 justify-content-center">
            @forelse($plans as $plan)
            <div class="col-md-4">
                <div class="pricing-card p-4 text-center {{ $plan->slug === 'pro' ? 'featured' : '' }}">
                    @if($plan->slug === 'pro')
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
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_daily_trades >= 9999 ? 'Unlimited' : $plan->max_daily_trades }} trades/day</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_concurrent_positions }} concurrent positions</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Tier 1: AI Reflexes (free PHP logic)</li>
                        <li class="mb-2"><i class="bi {{ $plan->has_ai_muscles ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>Tier 2: AI Muscles (Claude Haiku)</li>
                        <li class="mb-2"><i class="bi {{ $plan->has_ai_brain ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>Tier 3: AI Brain (Claude Sonnet)</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Full dashboard & analytics</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Telegram notifications</li>
                        @if($plan->trial_days > 0)
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->trial_days }}-day free trial</li>
                        @endif
                        @if($plan->features_json)
                            @foreach($plan->features_json as $feature)
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                    @auth
                        <a href="/subscription" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx' : 'btn-ptx-outline' }} w-100">
                            {{ auth()->user()->subscription_plan === $plan->slug ? 'Current Plan' : 'Upgrade' }}
                        </a>
                    @else
                        <a href="/register" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx' : 'btn-ptx-outline' }} w-100">Start Free Trial</a>
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
            <h5 class="fw-bold mb-3">All Plans Include</h5>
            <div class="row g-3 justify-content-center">
                <div class="col-md-3"><i class="bi bi-bar-chart-line text-primary me-2"></i>Full trading dashboard</div>
                <div class="col-md-3"><i class="bi bi-clock-history text-primary me-2"></i>Complete trade history</div>
                <div class="col-md-3"><i class="bi bi-graph-up text-primary me-2"></i>Equity curve tracking</div>
                <div class="col-md-3"><i class="bi bi-telegram text-primary me-2"></i>Telegram notifications</div>
                <div class="col-md-3"><i class="bi bi-toggles text-primary me-2"></i>Paper trading mode</div>
                <div class="col-md-3"><i class="bi bi-shield-check text-primary me-2"></i>Risk management tools</div>
                <div class="col-md-3"><i class="bi bi-sliders text-primary me-2"></i>Customizable strategy</div>
                <div class="col-md-3"><i class="bi bi-lock text-primary me-2"></i>Encrypted credentials</div>
            </div>
            <p class="text-muted small mt-4">Prices in USD. All payments in cryptocurrency via NOWPayments (BTC, ETH, USDC, and more). No credit card required.</p>
        </div>
    </div>
</section>
@endsection
