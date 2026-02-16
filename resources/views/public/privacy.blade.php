@extends('layouts.public')

@section('title', 'Privacy Policy — PolyTraderX')
@section('meta_description', 'Review the PolyTraderX Privacy Policy to understand what data we collect, how we use it, third-party services, retention, and your data rights.')

@section('content')
    <section class="ptx-section" style="padding-top: 140px;">
        <div class="container" style="max-width: 1000px;">
            <div class="mb-4">
                <h1 style="font-size: 2.5rem;">Privacy Policy</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Last updated: {{ date('F j, Y') }}</p>
            </div>

            <div class="ptx-legal-layout">
                {{-- Table of Contents --}}
                <aside class="ptx-legal-toc">
                    <h6>Contents</h6>
                    <ul>
                        <li><a href="#pp-1">1. What We Collect</a></li>
                        <li><a href="#pp-2">2. How We Use It</a></li>
                        <li><a href="#pp-3">3. Storage &amp; Security</a></li>
                        <li><a href="#pp-4">4. Third Parties</a></li>
                        <li><a href="#pp-5">5. What We Don't Collect</a></li>
                        <li><a href="#pp-6">6. Data Retention</a></li>
                        <li><a href="#pp-7">7. Your Rights</a></li>
                        <li><a href="#pp-8">8. Cookies</a></li>
                        <li><a href="#pp-9">9. Changes</a></li>
                        <li><a href="#pp-10">10. Contact</a></li>
                    </ul>
                </aside>

                {{-- Content --}}
                <div class="ptx-legal-content">
                    <h4 id="pp-1">1. Information We Collect</h4>
                    <p>We collect information you provide directly:</p>
                    <ul>
                        <li><strong>Account Information:</strong> Name, email address, timezone, Account ID (auto-generated PTX-XXXXXXXXXXXX)</li>
                        <li><strong>Telegram Chat ID:</strong> Stored when you link your Telegram account via our platform bot (optional)</li>
                        <li><strong>Simulation Data:</strong> Simulated trade history, simulated P&amp;L records, and simulation balance snapshots</li>
                        <li><strong>Usage Data:</strong> Bot activity logs, AI decision records, strategy parameter changes</li>
                    </ul>

                    <h4 id="pp-2">2. How We Use Your Information</h4>
                    <p>We use your information to:</p>
                    <ul>
                        <li>Operate a simulation-only strategy environment (no live trade execution)</li>
                        <li>Display your dashboard, simulation history, and analytics</li>
                        <li>Send notifications via our platform Telegram bot (if you link your account)</li>
                        <li>Run AI analysis (Claude Haiku/Sonnet) on market data to generate trade signals</li>
                        <li>Perform post-loss forensic audits to improve strategy</li>
                        <li>Process subscription payments</li>
                    </ul>

                    <h4 id="pp-3">3. Data Storage &amp; Security</h4>
                    <p>We use industry-standard security practices to protect your data. Your account and simulation data are stored in a secure database. PolyTraderX is a simulation platform: we do not execute trades for you and we do not hold or move your funds.</p>

                    <h4 id="pp-4">4. Third-Party Services</h4>
                    <p>We integrate with the following third-party services:</p>
                    <ul>
                        <li><strong>Polymarket:</strong> For public market data and strategy simulation context only</li>
                        <li><strong>Binance:</strong> For real-time crypto price feeds (public API, no user credentials needed)</li>
                        <li><strong>Anthropic (Claude AI):</strong> For AI market analysis and forensics (platform-provided API key, not yours)</li>
                        <li><strong>Telegram:</strong> For sending notifications via our platform bot (your Telegram Chat ID is stored if you link your account)</li>
                        <li><strong>NOWPayments:</strong> For processing cryptocurrency subscription payments</li>
                        <li><strong>Google:</strong> For OAuth authentication (optional)</li>
                    </ul>

                    <h4 id="pp-5">5. What We Do NOT Collect</h4>
                    <p>We want to be clear about what we do <strong>not</strong> collect or store:</p>
                    <ul>
                        <li>Polymarket API keys, API secrets, or API passphrases</li>
                        <li>Polymarket private keys or wallet seed phrases</li>
                        <li>Anthropic API keys (AI is provided by the platform)</li>
                        <li>Binance API keys (we use public price feeds only)</li>
                        <li>Personal Telegram bot tokens (we use a single platform bot)</li>
                        <li>Financial information beyond what's needed for crypto payments</li>
                    </ul>

                    <h4 id="pp-6">6. Data Retention</h4>
                    <p>We retain your simulation and account data for as long as your account is active. Upon account deletion, your data will be permanently removed within 30 days.</p>

                    <h4 id="pp-7">7. Your Rights</h4>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal data via your dashboard</li>
                        <li>Correct inaccurate data via your settings</li>
                        <li>Request deletion of your data and account</li>
                        <li>Export your trading data</li>
                        <li>Unlink your Telegram account at any time</li>
                        <li>Withdraw consent for data processing</li>
                    </ul>

                    <h4 id="pp-8">8. Cookies</h4>
                    <p>We use essential cookies for session management and authentication. No third-party tracking cookies are used unless Google Analytics is enabled (configurable by platform administrators).</p>

                    <h4 id="pp-9">9. Changes to This Policy</h4>
                    <p>We may update this privacy policy from time to time. We will notify you of significant changes via email or dashboard notification.</p>

                    <h4 id="pp-10">10. Contact</h4>
                    <p>For privacy-related inquiries, please visit our <a href="/contact">contact page</a>.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
