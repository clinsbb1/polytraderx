{{-- Shared pricing cards component — used on homepage + /pricing --}}
<div class="row g-4 justify-content-center">
    @forelse($plans as $plan)
    <div class="col-md-6 col-lg-4 reveal reveal-delay-{{ $loop->iteration }}">
        <div class="ptx-pricing-card {{ $plan->slug === 'pro' ? 'featured' : '' }}">
            @if($plan->slug === 'pro')
                <span class="ptx-pricing-badge">Most Popular</span>
            @endif
            <h4 class="mt-2">{{ $plan->name }}</h4>
            <div class="my-3">
                @if((float)$plan->price_usd === 0.0)
                    <span class="ptx-pricing-price">Free</span>
                    <br><span class="ptx-pricing-period">{{ $plan->trial_days }}-day trial</span>
                @else
                    <span class="ptx-pricing-price">${{ number_format((float)$plan->price_usd, 0) }}</span>
                    <span class="ptx-pricing-period">/{{ $plan->billing_period }}</span>
                @endif
            </div>
            <ul class="ptx-pricing-features">
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    {{ $plan->max_daily_trades >= 9999 ? 'Unlimited' : $plan->max_daily_trades }} trades/day
                </li>
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    {{ $plan->max_concurrent_positions }} concurrent positions
                </li>
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    Tier 1: AI Reflexes (rule-based)
                </li>
                <li>
                    <i class="bi {{ $plan->has_ai_muscles ? 'bi-check-circle-fill check' : 'bi-x-circle-fill cross' }}"></i>
                    Tier 2: AI Muscles (Claude Haiku)
                </li>
                <li>
                    <i class="bi {{ $plan->has_ai_brain ? 'bi-check-circle-fill check' : 'bi-x-circle-fill cross' }}"></i>
                    Tier 3: AI Brain (Claude Sonnet)
                </li>
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    Full dashboard &amp; analytics
                </li>
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    Telegram notifications
                </li>
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    Paper trading (DRY RUN)
                </li>
                @if($plan->trial_days > 0)
                <li>
                    <i class="bi bi-check-circle-fill check"></i>
                    {{ $plan->trial_days }}-day free trial
                </li>
                @endif
                @if($plan->features_json)
                    @foreach($plan->features_json as $feature)
                    <li>
                        <i class="bi bi-check-circle-fill check"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                @endif
            </ul>
            @auth
                <a href="/subscription" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx-primary' : 'btn-ptx-secondary' }} w-100">
                    {{ auth()->user()->subscription_plan === $plan->slug ? 'Current Plan' : 'Upgrade' }}
                </a>
            @else
                @if((float)$plan->price_usd === 0.0)
                    <a href="/register" class="btn btn-ptx-primary w-100">Start Free</a>
                @else
                    <a href="/register" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx-primary' : 'btn-ptx-secondary' }} w-100">Subscribe with Crypto</a>
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
    Your keys, your funds <span class="mx-2" style="opacity:.4;">|</span>
    Cancel anytime <span class="mx-2" style="opacity:.4;">|</span>
    Pay with crypto <span class="mx-2" style="opacity:.4;">|</span>
    7-day free trial
</p>
