@extends('layouts.public')

@section('title', 'PolyTraderX — AI-Powered Polymarket Trading Bot')

@section('content')
    {{-- Hero --}}
    <section class="ptx-hero">
        <div class="ptx-glow-blob ptx-glow-blob--hero"></div>
        <div class="container position-relative" style="z-index:1;">
            <h1 class="ptx-fade-in">
                <span class="ptx-gradient-text">Trade Smarter</span><br>on Polymarket
            </h1>
            <p class="lead ptx-fade-in ptx-fade-in-delay-1">
                AI-powered bot that trades 15-minute crypto prediction markets with a late-minute certainty strategy. Enter only when the outcome is near-certain.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap ptx-fade-in ptx-fade-in-delay-2">
                <a href="/register" class="btn btn-ptx-primary btn-lg px-4">Start Free Trial</a>
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
            <p class="ptx-section-subtitle reveal">Three simple steps to automated Polymarket trading</p>
            <div class="ptx-steps">
                <div class="ptx-step reveal">
                    <div class="ptx-step-number">1</div>
                    <div class="ptx-step-icon"><i class="bi bi-search"></i></div>
                    <h5>Monitor Markets</h5>
                    <p>The bot continuously scans Polymarket's 15-minute crypto prediction markets (BTC, ETH, SOL), cross-referencing with Binance spot prices in real-time.</p>
                    <div class="ptx-step-line"></div>
                </div>
                <div class="ptx-step reveal reveal-delay-1">
                    <div class="ptx-step-number">2</div>
                    <div class="ptx-step-icon"><i class="bi bi-cpu"></i></div>
                    <h5>AI Analysis</h5>
                    <p>A 3-tier AI system scores confidence. Only trades when confidence exceeds 92% in the final 60 seconds of each market cycle.</p>
                    <div class="ptx-step-line"></div>
                </div>
                <div class="ptx-step reveal reveal-delay-2">
                    <div class="ptx-step-number">3</div>
                    <div class="ptx-step-icon"><i class="bi bi-lightning-charge"></i></div>
                    <h5>Execute &amp; Learn</h5>
                    <p>Automatic order placement with risk management. Every loss triggers an AI forensic audit to continuously improve the strategy.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="ptx-section ptx-section--alt">
        <div class="container">
            <h2 class="ptx-section-title reveal">Built for Serious Traders</h2>
            <p class="ptx-section-subtitle reveal">Everything you need to trade Polymarket prediction markets with confidence</p>
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
                        'description' => 'Get notified for every trade, daily P&L summaries, error alerts, and AI audit results via our platform bot.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-3">
                    @include('components.feature-card', [
                        'icon' => 'bi-sliders',
                        'title' => 'Customizable Strategy',
                        'description' => 'Fine-tune every trading parameter: confidence thresholds, bet sizes, monitored assets, entry windows, and more.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-4">
                    @include('components.feature-card', [
                        'icon' => 'bi-toggles',
                        'title' => 'Paper Trading',
                        'description' => 'DRY_RUN mode lets you test strategies without risking real money. Full logging and analytics included.'
                    ])
                </div>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-5">
                    @include('components.feature-card', [
                        'icon' => 'bi-lock',
                        'title' => 'Security First',
                        'description' => 'All API keys encrypted at rest. No private keys stored. Your funds stay in your Polymarket account at all times.'
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

    {{-- CTA --}}
    <section class="ptx-cta">
        <div class="container position-relative" style="z-index:1;">
            <h2 class="fw-bold mb-3 reveal" style="font-size: 2.5rem;">Ready to Start Trading?</h2>
            <p class="lead mb-4 reveal reveal-delay-1" style="color: var(--text-secondary);">Join PolyTraderX and let AI handle your Polymarket trades.</p>
            <a href="/register" class="btn btn-ptx-primary btn-lg px-5 reveal reveal-delay-2" style="color: #00e6ff !important">Start Your Free Trial</a>
        </div>
    </section>
@endsection
