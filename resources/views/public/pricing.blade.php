@extends('layouts.public')

@section('title', 'Pricing — PolyTraderX')
@php
    $pricingMetaDescription = $freeModeEnabled
        ? 'Compare PolyTraderX plans for strategy simulation. Start free, upgrade to Pro or Advanced, and unlock AI analytics, priority support, and deeper performance tools.'
        : 'Compare PolyTraderX plans for strategy simulation. Choose Pro, Advanced, or Lifetime to unlock AI analytics, priority support, and deeper performance tools.';
@endphp
@section('meta_description', $pricingMetaDescription)

@section('content')
    {{-- Header --}}
    <section class="ptx-hero" style="padding: 140px 0 60px;">
        <div class="container position-relative" style="z-index:1;">
            <h1 class="ptx-fade-in" style="font-size: 3rem;">
                <span class="ptx-gradient-text">Simple, Transparent</span> Pricing
            </h1>
            <p class="lead ptx-fade-in ptx-fade-in-delay-1">
                {{ $freeModeEnabled ? 'Start simulating free. Upgrade when you\'re ready. Pay with crypto.' : 'Choose a plan and start simulating today. Pay with crypto.' }}
            </p>
        </div>
    </section>

    {{-- Pricing Cards --}}
    <section class="ptx-section" style="padding-top: 40px;">
        <div class="container">
            @include('components.pricing-cards', ['plans' => $plans])

            {{-- Simulation Disclaimer --}}
            <div class="text-center mt-4">
                <div class="alert alert-info d-inline-block" style="max-width: 700px; background: rgba(0, 230, 255, 0.1); border: 1px solid rgba(0, 230, 255, 0.3); color: var(--text-secondary);">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>All plans simulate strategies using real market data.</strong> No live trades are placed. Perfect for learning and strategy development.
                </div>
            </div>
        </div>
    </section>

    {{-- Feature Comparison Table --}}
    <section class="ptx-section ptx-section--alt">
        <div class="container" style="max-width: 900px;">
            <h2 class="ptx-section-title reveal">Feature Comparison</h2>
            <p class="ptx-section-subtitle reveal">See exactly what's included in each plan</p>

            <div class="glass-card reveal" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="ptx-compare-table">
                        <thead>
                            <tr>
                                <th>Feature</th>
                                @foreach($plans as $plan)
                                <th>{{ $plan->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Pricing</strong></td>
                                @foreach($plans as $plan)
                                <td>
                                    @if((float)$plan->price_usd === 0.0 && $freeModeEnabled && $plan->slug === 'free')
                                        Free
                                    @else
                                        ${{ number_format((float)$plan->price_usd, 0) }}/mo
                                        @if($plan->yearly_price)
                                            <br><small style="color: var(--text-secondary);">${{ number_format((float)$plan->yearly_price, 0) }}/yr</small>
                                        @endif
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td><strong>Simulation</strong></td>
                                @foreach($plans as $plan)<td></td>@endforeach
                            </tr>
                            <tr>
                                <td>
                                    Daily signals
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Maximum number of trading signals the simulator can generate per day"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->max_signals_per_day === 0 ? 'Unlimited' : $plan->max_signals_per_day }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    Concurrent positions
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="How many simulated positions you can have open at the same time"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->max_concurrent_positions }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    Historical data access
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Number of days of historical trade data and analytics you can access"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->historical_days }} days</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td><strong>AI Features</strong></td>
                                @foreach($plans as $plan)<td></td>@endforeach
                            </tr>
                            <tr>
                                <td>
                                    AI Muscles (Haiku) calls/day
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Fast AI analysis using Claude Haiku for quick market confidence scoring before signals"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->max_ai_muscles_calls_per_day === 0 ? 'Unlimited' : $plan->max_ai_muscles_calls_per_day }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    AI Brain (Sonnet) calls/day
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Deep AI analysis using Claude Sonnet for post-loss forensics and strategy audits (daily limit)"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->max_ai_brain_calls_per_day === 0 ? '—' : $plan->max_ai_brain_calls_per_day }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    AI Brain calls/month
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Monthly cap for AI Brain calls to ensure fair usage across all users"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->max_ai_brain_calls_per_month === 0 ? '—' : $plan->max_ai_brain_calls_per_month }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td><strong>Features</strong></td>
                                @foreach($plans as $plan)<td></td>@endforeach
                            </tr>
                            <tr>
                                <td>
                                    Strategy Health Metrics
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Advanced analytics showing drawdown, consistency score, win streaks, and strategy stability assessment"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{!! $plan->strategy_health_metrics ? '<i class="bi bi-check-circle-fill check-mark"></i>' : '<i class="bi bi-x-circle-fill cross-mark"></i>' !!}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    CSV Export
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Download all your trade data as CSV for analysis in Excel or other tools"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{!! $plan->csv_export_enabled ? '<i class="bi bi-check-circle-fill check-mark"></i>' : '<i class="bi bi-x-circle-fill cross-mark"></i>' !!}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    Telegram Notifications
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Get real-time alerts on Telegram for signals, trades, and important updates"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{!! $plan->telegram_enabled ? '<i class="bi bi-check-circle-fill check-mark"></i>' : '<i class="bi bi-x-circle-fill cross-mark"></i>' !!}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    Priority Processing
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Your signals get evaluated first in the queue for faster execution during high-traffic periods"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{!! $plan->priority_processing ? '<i class="bi bi-check-circle-fill check-mark"></i>' : '<i class="bi bi-x-circle-fill cross-mark"></i>' !!}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td>
                                    Support Level
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.85rem; color: var(--text-secondary); cursor: help;"
                                       data-bs-toggle="tooltip" data-bs-placement="right"
                                       data-bs-title="Priority support includes faster support handling for higher-tier plans"></i>
                                </td>
                                @foreach($plans as $plan)
                                <td>{{ $plan->priority_processing ? 'Priority Support' : 'Standard Support' }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Simulation Notice --}}
            <div class="glass-card reveal mt-4" style="border-left: 4px solid var(--accent-cyan); background: rgba(0, 230, 255, 0.05);">
                <div class="d-flex align-items-start gap-3">
                    <div style="font-size: 2rem; color: var(--accent-cyan); flex-shrink: 0;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-2" style="color: var(--accent-cyan);">100% Simulation Platform</h5>
                        <p class="mb-0" style="color: var(--text-secondary); line-height: 1.6;">
                            PolyTraderX is a strategy simulation and analysis tool. <strong>All activity is simulated using real market data.</strong>
                            No live trades are placed. No real money is at risk. The platform is designed for strategy design, testing, and learning.
                            Live trading may be supported in the future, but simulation comes first.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="ptx-section">
        <div class="container" style="max-width: 800px;">
            <h2 class="ptx-section-title reveal">Frequently Asked Questions</h2>
            <p class="ptx-section-subtitle reveal">Everything you need to know about PolyTraderX</p>

            <div class="accordion ptx-accordion" id="billingFaq">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#bfaq1">What cryptocurrencies do you accept?</button>
                    </h2>
                    <div id="bfaq1" class="accordion-collapse collapse show" data-bs-parent="#billingFaq">
                        <div class="accordion-body">We accept BTC, ETH, USDC, USDT, SOL, and many other cryptocurrencies via NOWPayments. Over 100 cryptocurrencies are supported.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bfaq2">Can I cancel anytime?</button>
                    </h2>
                    <div id="bfaq2" class="accordion-collapse collapse" data-bs-parent="#billingFaq">
                        <div class="accordion-body">Yes. You can cancel your subscription at any time. Your access continues until the end of the current billing period. No refunds for partial periods.</div>
                    </div>
                </div>
                @if($freeModeEnabled)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bfaq3">How does the Free plan work?</button>
                        </h2>
                        <div id="bfaq3" class="accordion-collapse collapse" data-bs-parent="#billingFaq">
                            <div class="accordion-body">Sign up for free—no payment required, no credit card needed. You get immediate access to the simulator with 10 signals per day and basic features. All activity is simulated using real market data. Upgrade to a paid plan anytime for more signals, AI Brain analysis, and advanced features like Strategy Health Metrics and Telegram notifications.</div>
                        </div>
                    </div>
                @endif
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bfaq4">Do I need a credit card?</button>
                    </h2>
                    <div id="bfaq4" class="accordion-collapse collapse" data-bs-parent="#billingFaq">
                        <div class="accordion-body">No. We only accept cryptocurrency payments. No credit card, bank account, or traditional payment method is needed. This ensures privacy and global accessibility.</div>
                    </div>
                </div>
            </div>

            <p class="text-center mt-4 reveal" style="color: var(--text-secondary); font-size: 0.9rem;">
                All plans are billed in cryptocurrency via NOWPayments. Prices shown in USD equivalent.
            </p>
        </div>
    </section>
@endsection

@section('scripts')
<script>
// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
