@extends('layouts.public')

@section('title', 'Pricing — PolyTraderX')

@section('content')
    {{-- Header --}}
    <section class="ptx-hero" style="padding: 140px 0 60px;">
        <div class="container position-relative" style="z-index:1;">
            <h1 class="ptx-fade-in" style="font-size: 3rem;">
                <span class="ptx-gradient-text">Simple, Transparent</span> Pricing
            </h1>
            <p class="lead ptx-fade-in ptx-fade-in-delay-1">
                Start with a free trial. Upgrade when you're ready. Pay with crypto.
            </p>
        </div>
    </section>

    {{-- Pricing Cards --}}
    <section class="ptx-section" style="padding-top: 40px;">
        <div class="container">
            @include('components.pricing-cards', ['plans' => $plans])
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
                                <th>Free Trial</th>
                                <th>Basic</th>
                                <th>Pro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Daily trades</td>
                                <td>10</td>
                                <td>50</td>
                                <td>Unlimited</td>
                            </tr>
                            <tr>
                                <td>Concurrent positions</td>
                                <td>1</td>
                                <td>3</td>
                                <td>5</td>
                            </tr>
                            <tr>
                                <td>Tier 1: AI Reflexes</td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Tier 2: AI Muscles (Haiku)</td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Tier 3: AI Brain (Sonnet)</td>
                                <td><i class="bi bi-x-circle-fill cross-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Telegram notifications</td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Post-loss forensic audits</td>
                                <td><i class="bi bi-x-circle-fill cross-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Paper trading (DRY RUN)</td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Full dashboard &amp; analytics</td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                            <tr>
                                <td>Priority support</td>
                                <td><i class="bi bi-x-circle-fill cross-mark"></i></td>
                                <td><i class="bi bi-x-circle-fill cross-mark"></i></td>
                                <td><i class="bi bi-check-circle-fill check-mark"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- Billing FAQ --}}
    <section class="ptx-section">
        <div class="container" style="max-width: 800px;">
            <h2 class="ptx-section-title reveal">Billing FAQ</h2>
            <p class="ptx-section-subtitle reveal">Common questions about payments and subscriptions</p>

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
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bfaq3">How does the free trial work?</button>
                    </h2>
                    <div id="bfaq3" class="accordion-collapse collapse" data-bs-parent="#billingFaq">
                        <div class="accordion-body">Sign up for free and get 7 days of access with no payment required. Use paper trading mode to test strategies risk-free. Upgrade to a paid plan when you're ready to trade live.</div>
                    </div>
                </div>
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
