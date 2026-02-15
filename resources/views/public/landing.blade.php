@extends('layouts.public')

@section('title', 'PolyTraderX — AI-Powered Strategy Lab for Polymarket')
@section('meta_description', 'Design, simulate, and optimize Polymarket crypto prediction strategies with AI-driven analysis. Test your approach safely using real market data and no real-money risk.')

@section('content')
    {{-- Hero --}}
    <section class="ptx-hero">
        <div class="ptx-glow-blob ptx-glow-blob--hero"></div>
        <div class="container position-relative" style="z-index:1;">
            <h1 class="ptx-fade-in">
                <span class="ptx-gradient-text">Design. Simulate. Analyze.</span><br>Master Polymarket Strategy
            </h1>
            <p class="lead ptx-fade-in ptx-fade-in-delay-1">
                PolyTraderX is an AI-powered strategy lab for Polymarket's crypto prediction markets. Design, simulate, and analyze strategies using real market data — without risking real money. Your simulator continuously learns from signals and outcomes, improving its recommendations over time.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap ptx-fade-in ptx-fade-in-delay-2">
                <a href="/register" class="btn btn-ptx-primary btn-lg px-4">Start Simulating Free</a>
                <a href="#pricing" class="btn btn-ptx-secondary btn-lg px-4">See Pricing</a>
            </div>
            <div class="ptx-trust-row ptx-fade-in ptx-fade-in-delay-3">
                <span>No credit card required</span>
                <span class="dot"></span>
                <span>7-day free trial</span>
                <span class="dot"></span>
                <span>Pay with crypto</span>
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section id="how-it-works" class="ptx-section">
        <div class="container">
            <h2 class="ptx-section-title reveal">How It Works</h2>
            <p class="ptx-section-subtitle reveal">Three simple steps to strategy simulation</p>
            <div class="ptx-steps">
                <div class="ptx-step reveal">
                    <div class="ptx-step-number">1</div>
                    <div class="ptx-step-icon"><i class="bi bi-search"></i></div>
                    <h5>Monitor Markets</h5>
                    <p>Your simulator continuously scans Polymarket's 5 or 15 minute crypto prediction markets (BTC, ETH, SOL) based on your preferences, cross-referencing real-time Binance spot prices to inform each signal.</p>
                    <div class="ptx-step-line"></div>
                </div>
                <div class="ptx-step reveal reveal-delay-1">
                    <div class="ptx-step-number">2</div>
                    <div class="ptx-step-icon"><i class="bi bi-cpu"></i></div>
                    <h5>AI Analysis</h5>
                    <p>Your simulator evaluates market conditions using your custom strategy and confidence rules, generating signals only when your defined criteria are met.</p>
                    <div class="ptx-step-line"></div>
                </div>
                <div class="ptx-step reveal reveal-delay-2">
                    <div class="ptx-step-number">3</div>
                    <div class="ptx-step-icon"><i class="bi bi-lightning-charge"></i></div>
                    <h5>Track &amp; Learn</h5>
                    <p>Simulated position tracking with full performance analytics. Every losing signal triggers an AI forensic audit to continuously improve the strategy.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="ptx-section ptx-section--alt">
        <div class="container">
            <h2 class="ptx-section-title reveal">Built for Strategy Designers</h2>
            <p class="ptx-section-subtitle reveal">Everything you need to design and test Polymarket strategies with confidence</p>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4 reveal">
                    @include('components.feature-card', [
                        'icon' => 'bi-shield-check',
                        'title' => 'Risk Management',
                        'description' => 'Configurable max bet, daily loss limits, concurrent position limits, drawdown alerts, and automatic kill switch.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-1">
                    @include('components.feature-card', [
                        'icon' => 'bi-bar-chart-line',
                        'title' => 'Full Dashboard',
                        'description' => 'Real-time equity curves, trade history, win rate tracking, AI cost monitoring, and detailed forensic logs.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-2">
                    @include('components.feature-card', [
                        'icon' => 'bi-telegram',
                        'title' => 'Telegram Notifications',
                        'description' => 'Get notified for every signal, daily P&L summaries, error alerts, and AI audit results via our platform bot.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-3">
                    @include('components.feature-card', [
                        'icon' => 'bi-sliders',
                        'title' => 'Customizable Strategy',
                        'description' => 'Fine-tune every parameter: confidence thresholds, position sizes, monitored assets, entry windows, and more.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-4">
                    @include('components.feature-card', [
                        'icon' => 'bi-toggles',
                        'title' => 'Strategy Simulation',
                        'description' => 'All activity is simulated using real market data. Test strategies without risking real money. Full logging and analytics included.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-5">
                    @include('components.feature-card', [
                        'icon' => 'bi-lock',
                        'title' => 'Risk-Free Testing',
                        'description' => 'No real money at risk. All positions are simulated. Perfect for learning, testing, and refining strategies before considering live execution.'
                    ])
                </div>
            </div>
        </div>
    </section>

    {{-- AI Explainer --}}
    <section id="how-ai-works" class="ptx-section">
        <div class="container" style="max-width: 800px;">
            <h2 class="ptx-section-title reveal">How the AI Works</h2>
            <p class="ptx-section-subtitle reveal">A 3-tier architecture that optimizes cost without sacrificing intelligence</p>
            @include('components.ai-layers')
        </div>
    </section>

    {{-- Stats Bar --}}
    @include('components.stat-bar')

    {{-- Pricing --}}
    @if($plans->count() > 0)
    <section id="pricing" class="ptx-section ptx-section--alt">
        <div class="container">
            <h2 class="ptx-section-title reveal">Simple, Transparent Pricing</h2>
            <p class="ptx-section-subtitle reveal">Start free, upgrade when you're ready. All plans paid in crypto.</p>
            @include('components.pricing-cards', ['plans' => $plans])
        </div>
    </section>
    @endif

    {{-- FAQ --}}
    <section class="ptx-section">
        <div class="container" style="max-width: 800px;">
            <h2 class="ptx-section-title reveal">Frequently Asked Questions</h2>
            <p class="ptx-section-subtitle reveal">Everything you need to know before getting started</p>
            @include('components.faq-accordion')
        </div>
    </section>

    {{-- Simulation Notice --}}
    <section class="ptx-section">
        <div class="container" style="max-width: 800px;">
            <div class="glass-card reveal" style="border-left: 4px solid var(--accent-cyan); background: rgba(0, 230, 255, 0.05);">
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

    {{-- CTA --}}
    <section class="ptx-cta">
        <div class="container position-relative" style="z-index:1;">
            <h2 class="fw-bold mb-3 reveal" style="font-size: 2.5rem;">Ready to Design Your Strategy?</h2>
            <p class="lead mb-4 reveal reveal-delay-1" style="color: var(--text-secondary);">Join PolyTraderX and let AI help you analyze Polymarket strategies.</p>
            <a href="/register" class="btn btn-ptx-primary btn-lg px-5 reveal reveal-delay-2" style="color: #00e6ff !important">Start Simulating Free</a>
        </div>
    </section>
@endsection
