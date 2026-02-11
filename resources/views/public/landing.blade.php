@extends('layouts.public')

@section('title', 'PolyTraderX — Automated Polymarket Trading Bot')

@section('content')
    <!-- Hero -->
    <section class="hero-section text-center">
        <div class="container">
            <h1>Trade Smarter on Polymarket</h1>
            <p class="lead mt-3 mb-4" style="max-width:700px;margin:0 auto;">AI-powered bot that trades 15-minute crypto prediction markets with a late-minute certainty strategy. Enter only when the outcome is near-certain.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/register" class="btn btn-ptx btn-lg px-4">Start Free Trial</a>
                <a href="#how-it-works" class="btn btn-ptx-outline btn-lg px-4">How It Works</a>
            </div>
            <p class="small mt-3 opacity-75">7-day free trial. No credit card required. Pay with crypto.</p>
        </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-5">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="feature-icon mx-auto mb-3"><i class="bi bi-clock-history"></i></div>
                    <h5>1. Monitor Markets</h5>
                    <p class="text-muted">The bot continuously scans Polymarket's 15-minute crypto prediction markets (BTC, ETH, SOL), cross-referencing with Binance spot prices.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon mx-auto mb-3"><i class="bi bi-cpu"></i></div>
                    <h5>2. AI Analysis</h5>
                    <p class="text-muted">A 3-tier AI system (Reflexes + Muscles + Brain) scores confidence. Only trades when confidence exceeds 92% in the final 60 seconds.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon mx-auto mb-3"><i class="bi bi-lightning-charge"></i></div>
                    <h5>3. Execute & Learn</h5>
                    <p class="text-muted">Automatic order placement with risk management. Every loss triggers an AI forensic audit to continuously improve the strategy.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Features</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-robot"></i></div>
                            <h5>3-Tier AI Architecture</h5>
                            <p class="text-muted">Free rule-based Reflexes, cheap Haiku for scoring, expensive Sonnet for forensics. Optimized AI cost.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-shield-check"></i></div>
                            <h5>Risk Management</h5>
                            <p class="text-muted">Configurable max bet, daily loss limits, concurrent position limits, and automatic kill switch.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-bar-chart-line"></i></div>
                            <h5>Full Dashboard</h5>
                            <p class="text-muted">Real-time equity curves, trade history, AI cost tracking, and detailed forensic logs.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-telegram"></i></div>
                            <h5>Telegram Alerts</h5>
                            <p class="text-muted">Get notified for every trade, daily P&L summaries, error alerts, and AI audit results.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-toggles"></i></div>
                            <h5>Paper Trading</h5>
                            <p class="text-muted">DRY_RUN mode lets you test strategies without risking real money. Full logging included.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-currency-bitcoin"></i></div>
                            <h5>Crypto Payments</h5>
                            <p class="text-muted">Pay for your subscription with cryptocurrency via NOWPayments. No credit card needed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    @if($plans->count() > 0)
    <section class="py-5">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Simple Pricing</h2>
            <div class="row g-4 justify-content-center">
                @foreach($plans as $plan)
                <div class="col-md-4">
                    <div class="pricing-card p-4 text-center {{ $plan->slug === 'basic' ? 'featured' : '' }}">
                        @if($plan->slug === 'basic')
                            <span class="pricing-badge">Most Popular</span>
                        @endif
                        <h4 class="fw-bold mt-2">{{ $plan->name }}</h4>
                        <div class="my-3">
                            <span class="display-5 fw-bold">${{ number_format((float)$plan->price_usd, 0) }}</span>
                            <span class="text-muted">/{{ $plan->billing_period }}</span>
                        </div>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_daily_trades ?: 'Unlimited' }} trades/day</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_concurrent_positions ?: 'Unlimited' }} concurrent positions</li>
                            <li class="mb-2"><i class="bi {{ $plan->has_ai_muscles ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>AI Muscles (Haiku)</li>
                            <li class="mb-2"><i class="bi {{ $plan->has_ai_brain ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>AI Brain (Sonnet)</li>
                        </ul>
                        <a href="/register" class="btn {{ $plan->slug === 'basic' ? 'btn-ptx' : 'btn-ptx-outline' }} w-100">Get Started</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- FAQ -->
    <section class="py-5 bg-light">
        <div class="container" style="max-width:800px">
            <h2 class="text-center fw-bold mb-5">FAQ</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">What is the late-minute certainty strategy?</button></h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">The bot only enters trades in the final 30-60 seconds of each 15-minute cycle, when the outcome is near-certain (>92% confidence). By waiting until the last moment, we minimize risk while capturing guaranteed payouts.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">What do I need to get started?</button></h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">You need a Polymarket account with API keys, and optionally a Telegram bot for notifications and an Anthropic API key for AI features. The onboarding wizard will guide you through setup.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Is there a risk of losing money?</button></h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">Yes. While the strategy aims for high-confidence trades, prediction markets carry inherent risk. API desyncs, unexpected market moves, or technical failures can result in losses. Always use the paper trading mode first and never trade with money you cannot afford to lose.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">How do I pay?</button></h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">We accept cryptocurrency payments via NOWPayments. You can pay with BTC, ETH, USDC, and many other cryptocurrencies. A 7-day free trial is available to get started.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-5 text-center" style="background: linear-gradient(135deg, var(--ptx-dark) 0%, #312e81 100%); color: #fff;">
        <div class="container">
            <h2 class="fw-bold mb-3">Ready to Start Trading?</h2>
            <p class="lead mb-4">Join PolyTraderX and let AI handle your Polymarket trades.</p>
            <a href="/register" class="btn btn-ptx btn-lg px-5">Start Your Free Trial</a>
        </div>
    </section>
@endsection
