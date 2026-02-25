{{-- Shared pricing cards component — used on homepage + /pricing --}}
<div class="row g-4 justify-content-center">
    @forelse($plans as $plan)
    <div class="col-md-6 col-lg-3 reveal reveal-delay-{{ $loop->iteration }}">
        <div class="ptx-pricing-card {{ $plan->slug === 'pro' ? 'featured' : '' }}">
            @if($plan->slug === 'pro')
                <span class="ptx-pricing-badge">Most Popular</span>
            @elseif($plan->slug === 'lifetime')
                <span class="ptx-pricing-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    Limited: few spots left
                </span>
            @endif
            <h4 class="mt-2">{{ $plan->name }}</h4>
            <div class="my-3">
                @if((float)$plan->price_usd === 0.0)
                    @if($freeModeEnabled && $plan->slug === 'free')
                        <span class="ptx-pricing-price">Free</span>
                        <br><span class="ptx-pricing-period">{{ $plan->trial_days }}-day trial</span>
                    @else
                        <span class="ptx-pricing-price">${{ number_format((float)$plan->price_usd, 0) }}</span>
                        <span class="ptx-pricing-period">/{{ $plan->billing_period }}</span>
                    @endif
                @else
                    <span class="ptx-pricing-price">${{ number_format((float)$plan->price_usd, 0) }}</span>
                    <span class="ptx-pricing-period">/{{ $plan->billing_period }}</span>
                @endif
            </div>
            <ul class="ptx-pricing-features">
                <li>
                    <i class="bi bi-lightning-charge-fill"></i>
                    <strong>{{ $plan->max_signals_per_day === 0 ? 'Unlimited' : $plan->max_signals_per_day }}</strong> signals/day
                </li>
                <li>
                    <i class="bi bi-cpu-fill"></i>
                    @if($plan->ai_brain_enabled)
                        AI Brain + Muscles
                    @elseif($plan->ai_muscles_enabled)
                        AI Muscles only
                    @else
                        Reflexes only
                    @endif
                </li>
                <li>
                    <i class="bi {{ $plan->strategy_health_metrics ? 'bi-heart-pulse-fill' : 'bi-x-circle-fill cross' }}"></i>
                    Strategy Health Metrics
                </li>
                <li>
                    <i class="bi {{ $plan->telegram_enabled ? 'bi-telegram' : 'bi-x-circle-fill cross' }}"></i>
                    Telegram Notifications
                </li>
                <li>
                    <i class="bi {{ $plan->priority_processing ? 'bi-headset' : 'bi-headset cross' }}"></i>
                    {{ $plan->priority_processing ? 'Priority Support' : 'Standard Support' }}
                </li>
                @if($plan->slug === 'lifetime')
                <li>
                    <i class="bi bi-infinity"></i>
                    <strong>Lifetime access</strong>
                </li>
                @endif
            </ul>
            @auth
                <a href="/subscription" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx-primary' : 'btn-ptx-secondary' }} w-100">
                    {{ auth()->user()->subscription_plan === $plan->slug ? 'Current Plan' : 'Upgrade' }}
                </a>
            @else
                @if((float)$plan->price_usd === 0.0 && $freeModeEnabled && $plan->slug === 'free')
                    <a href="/register" class="btn btn-ptx-primary w-100">Start Free</a>
                @else
                    <a href="/register" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx-primary' : 'btn-ptx-secondary' }} w-100">Subscribe</a>
                @endif
            @endauth
        </div>
    </div>
    @empty
    <div class="col-12 text-center">
        <p style="color: var(--text-secondary);">Pricing plans coming soon.</p>
    </div>
    @endforelse
</div>
<p class="text-center mt-4" style="color: var(--text-secondary); font-size: 0.9rem;">
    Cancel anytime <span class="mx-2" style="opacity:.4;">|</span>
    Pay with crypto
    @if($freeModeEnabled)
        <span class="mx-2" style="opacity:.4;">|</span>
        7-day free trial
    @endif
</p>
