@extends('layouts.public')

@section('title', 'PolyTraderX — AI-Powered Polymarket Trading Bot')

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
                    <p class="text-muted">The bot continuously scans Polymarket's 15-minute crypto prediction markets (BTC, ETH, SOL), cross-referencing with Binance spot prices in real-time.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon mx-auto mb-3"><i class="bi bi-cpu"></i></div>
                    <h5>2. AI Analysis</h5>
                    <p class="text-muted">A 3-tier AI system scores confidence. Only trades when confidence exceeds 92% in the final 60 seconds of each market cycle.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon mx-auto mb-3"><i class="bi bi-lightning-charge"></i></div>
                    <h5>3. Execute & Learn</h5>
                    <p class="text-muted">Automatic order placement with risk management. Every loss triggers an AI forensic audit to continuously improve the strategy.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3-Tier AI -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">3-Tier AI Architecture</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mx-auto mb-3"><i class="bi bi-lightning"></i></div>
                            <h5>Tier 1: Reflexes</h5>
                            <p class="small text-muted mb-0">Free rule-based logic. Runs every minute. Market scanning, price checks, pattern matching. Zero AI cost.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mx-auto mb-3"><i class="bi bi-robot"></i></div>
                            <h5>Tier 2: Muscles</h5>
                            <p class="small text-muted mb-0">Claude Haiku (fast & cheap). Runs every 5 minutes. Confidence scoring, pattern recognition, market sentiment analysis.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mx-auto mb-3"><i class="bi bi-stars"></i></div>
                            <h5>Tier 3: Brain</h5>
                            <p class="small text-muted mb-0">Claude Sonnet (powerful). On-demand only. Post-loss forensics, strategy optimization, weekly deep analysis reports.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Features</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-shield-check"></i></div>
                            <h5>Risk Management</h5>
                            <p class="text-muted">Configurable max bet, daily loss limits, concurrent position limits, drawdown alerts, and automatic kill switch.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-bar-chart-line"></i></div>
                            <h5>Full Dashboard</h5>
                            <p class="text-muted">Real-time equity curves, trade history, win rate tracking, AI cost monitoring, and detailed forensic logs.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-telegram"></i></div>
                            <h5>Telegram Notifications</h5>
                            <p class="text-muted">Get notified for every trade, daily P&L summaries, error alerts, and AI audit results via our platform bot.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-sliders"></i></div>
                            <h5>Customizable Strategy</h5>
                            <p class="text-muted">Fine-tune every trading parameter: confidence thresholds, bet sizes, monitored assets, entry windows, and more.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-toggles"></i></div>
                            <h5>Paper Trading</h5>
                            <p class="text-muted">DRY_RUN mode lets you test strategies without risking real money. Full logging and analytics included.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="feature-icon mb-3"><i class="bi bi-lock"></i></div>
                            <h5>Security First</h5>
                            <p class="text-muted">All API keys encrypted at rest. No private keys stored. Read-only API access where possible. Your funds stay in your Polymarket account.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    @if($plans->count() > 0)
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Simple Pricing</h2>
            <div class="row g-4 justify-content-center">
                @foreach($plans as $plan)
                <div class="col-md-4">
                    <div class="pricing-card p-4 text-center {{ $plan->slug === 'pro' ? 'featured' : '' }}">
                        @if($plan->slug === 'pro')
                            <span class="pricing-badge">Most Popular</span>
                        @endif
                        <h4 class="fw-bold mt-2">{{ $plan->name }}</h4>
                        <div class="my-3">
                            <span class="display-5 fw-bold">${{ number_format((float)$plan->price_usd, 0) }}</span>
                            <span class="text-muted">/{{ $plan->billing_period }}</span>
                        </div>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_daily_trades >= 9999 ? 'Unlimited' : $plan->max_daily_trades }} trades/day</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->max_concurrent_positions }} concurrent positions</li>
                            <li class="mb-2"><i class="bi {{ $plan->has_ai_muscles ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>AI Muscles (Haiku)</li>
                            <li class="mb-2"><i class="bi {{ $plan->has_ai_brain ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }} me-2"></i>AI Brain (Sonnet)</li>
                            @if($plan->trial_days > 0)
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->trial_days }}-day free trial</li>
                            @endif
                        </ul>
                        <a href="/register" class="btn {{ $plan->slug === 'pro' ? 'btn-ptx' : 'btn-ptx-outline' }} w-100">Get Started</a>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-center text-muted mt-3 small">All plans paid in cryptocurrency via NOWPayments.</p>
        </div>
    </section>
    @endif

    <!-- FAQ -->
    <section class="py-5">
        <div class="container" style="max-width:800px">
            <h2 class="text-center fw-bold mb-5">Frequently Asked Questions</h2>
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
                        <div class="accordion-body">You need a Polymarket account with API keys (API Key, Secret, Passphrase, and Wallet Address). That's it! AI is provided by the platform, and Telegram notifications can be set up with one command. No Anthropic key or private keys required.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Is there a risk of losing money?</button></h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">Yes. While the strategy aims for high-confidence trades, prediction markets carry inherent risk. API desyncs, unexpected market moves, or technical failures can result in losses. Always use paper trading mode first and never trade with money you cannot afford to lose.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">How do I pay?</button></h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">We accept cryptocurrency payments via NOWPayments. You can pay with BTC, ETH, USDC, and many other cryptocurrencies. A 7-day free trial is available to get started.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Do you store my private keys?</button></h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">No. We never ask for or store your private keys. We only need your Polymarket API credentials (API Key, Secret, and Passphrase) which provide limited trading access. Your funds remain in your Polymarket wallet at all times.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">How does the 3-tier AI work?</button></h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">Tier 1 (Reflexes) is free logic that scans markets every minute. Tier 2 (Muscles) uses Claude Haiku every 5 minutes for confidence scoring. Tier 3 (Brain) uses Claude Sonnet on-demand for post-loss analysis and strategy review. This tiered approach optimizes AI costs.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">Can I customize the trading strategy?</button></h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">Yes! All strategy parameters are fully customizable: confidence thresholds, bet sizes, daily trade limits, entry window timing, monitored assets, notification preferences, and more. Every change is logged for audit purposes.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">How do Telegram notifications work?</button></h2>
                    <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">We have a platform-wide Telegram bot. After registering, go to Settings > Telegram, copy your Account ID, and send it to our bot. You'll receive real-time trade notifications, daily summaries, and alerts — all configurable.</div>
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
