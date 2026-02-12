@extends('layouts.public')

@section('title', 'Terms of Service — PolyTraderX')

@section('content')
    <section class="ptx-section" style="padding-top: 140px;">
        <div class="container" style="max-width: 1000px;">
            <div class="mb-4">
                <span class="ptx-draft-badge">Draft</span>
                <h1 style="font-size: 2.5rem;">Terms of Service</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Last updated: {{ date('F j, Y') }}</p>
            </div>

            <div class="ptx-legal-layout">
                {{-- Table of Contents --}}
                <aside class="ptx-legal-toc">
                    <h6>Contents</h6>
                    <ul>
                        <li><a href="#tos-1">1. Acceptance</a></li>
                        <li><a href="#tos-2">2. Description</a></li>
                        <li><a href="#tos-3">3. Risk Disclaimer</a></li>
                        <li><a href="#tos-4">4. Responsibilities</a></li>
                        <li><a href="#tos-5">5. Credentials</a></li>
                        <li><a href="#tos-6">6. Platform Services</a></li>
                        <li><a href="#tos-7">7. Payments</a></li>
                        <li><a href="#tos-8">8. Liability</a></li>
                        <li><a href="#tos-9">9. Availability</a></li>
                        <li><a href="#tos-10">10. Termination</a></li>
                        <li><a href="#tos-11">11. Changes</a></li>
                        <li><a href="#tos-12">12. Contact</a></li>
                    </ul>
                </aside>

                {{-- Content --}}
                <div class="ptx-legal-content">
                    <h4 id="tos-1">1. Acceptance of Terms</h4>
                    <p>By accessing and using PolyTraderX ("the Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, do not use the Service.</p>

                    <h4 id="tos-2">2. Description of Service</h4>
                    <p>PolyTraderX is an automated trading bot platform for Polymarket prediction markets. The Service connects to your Polymarket account using API credentials you provide (API Key, Secret, and Passphrase) and executes trades based on algorithmic and AI-driven strategies. We do not require or store your private keys — your funds remain in your Polymarket wallet at all times.</p>

                    <h4 id="tos-3">3. Risk Disclaimer</h4>
                    <div class="alert alert-danger">
                        <strong>IMPORTANT — PLEASE READ CAREFULLY:</strong>
                        <p class="mb-2 mt-2">Trading on prediction markets involves <strong>significant risk of financial loss</strong>. By using PolyTraderX, you explicitly acknowledge and accept the following:</p>
                        <ul class="mb-0">
                            <li><strong>You may lose some or all of your invested capital.</strong> There is no guarantee of profit.</li>
                            <li>Automated trading systems can and do malfunction, experience API desyncs, network failures, or make incorrect trading decisions.</li>
                            <li>Past performance, win rates, and confidence scores do <strong>not guarantee future results</strong>.</li>
                            <li>Market conditions can change rapidly and unpredictably, even within the final seconds of a trading cycle.</li>
                            <li>The "high confidence" strategy (>92% threshold) does not eliminate risk — unexpected reversals, API latency, and price manipulation can cause losses.</li>
                            <li>AI analysis (Claude Haiku/Sonnet) provides probabilistic assessments, not certainties. AI models can be wrong.</li>
                            <li>You are <strong>solely responsible</strong> for all trading decisions and their financial outcomes.</li>
                            <li>PolyTraderX is <strong>not a financial advisor</strong> and does not provide financial, investment, or legal advice.</li>
                            <li>You should <strong>never trade with money you cannot afford to lose</strong>.</li>
                            <li>You should always test with Paper Trading (DRY_RUN) mode before using real funds.</li>
                        </ul>
                    </div>

                    <h4 id="tos-4">4. User Responsibilities</h4>
                    <p>You are responsible for:</p>
                    <ul>
                        <li>Securing your Polymarket API credentials (API Key, Secret, Passphrase)</li>
                        <li>Configuring appropriate risk management parameters (max bet, daily loss limits, etc.)</li>
                        <li>Monitoring your account, trading activity, and Polymarket balance</li>
                        <li>Compliance with all applicable laws and regulations in your jurisdiction</li>
                        <li>Only trading with funds you can afford to lose entirely</li>
                        <li>Testing strategies in Paper Trading mode before live trading</li>
                        <li>Maintaining a funded Polymarket account</li>
                    </ul>

                    <h4 id="tos-5">5. API Credentials &amp; Security</h4>
                    <p>We only require your Polymarket API Key, API Secret, and API Passphrase to execute trades on your behalf. We also store your Polymarket Wallet Address for balance queries. All credentials are encrypted at rest using industry-standard encryption. We <strong>never</strong> ask for or store your private keys. We <strong>never</strong> share your credentials with third parties. However, you acknowledge that no system is completely secure and assume the risk of any unauthorized access.</p>

                    <h4 id="tos-6">6. Platform-Provided Services</h4>
                    <p>AI analysis (Claude Haiku and Claude Sonnet) and Telegram notifications are provided by the platform. You do not need to supply your own API keys for these services. AI costs are managed by the platform and included in your subscription fee.</p>

                    <h4 id="tos-7">7. Subscription &amp; Payments</h4>
                    <p>Subscription payments are processed via NOWPayments in cryptocurrency. Payments are non-refundable once confirmed on the blockchain. Subscriptions may be cancelled at any time, effective at the end of the current billing period. Free trials provide full access for the specified duration with no payment required.</p>

                    <h4 id="tos-8">8. Limitation of Liability</h4>
                    <p>To the maximum extent permitted by law, PolyTraderX, its operators, developers, and affiliates shall not be liable for any direct, indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, trading capital, data, or business opportunities. This includes losses caused by system malfunctions, API failures, incorrect AI predictions, market volatility, or any other cause.</p>

                    <h4 id="tos-9">9. No Guarantee of Availability</h4>
                    <p>We strive for high uptime but do not guarantee uninterrupted service. Planned maintenance, server failures, third-party API outages (Polymarket, Binance), or other events may cause service interruptions. You should not rely solely on the bot to manage your positions.</p>

                    <h4 id="tos-10">10. Termination</h4>
                    <p>We reserve the right to terminate or suspend your account at any time for violation of these terms or for any other reason at our sole discretion. Upon termination, all open positions remain in your Polymarket account (we do not close them).</p>

                    <h4 id="tos-11">11. Changes to Terms</h4>
                    <p>We may update these terms from time to time. Continued use of the Service after changes constitutes acceptance of the new terms.</p>

                    <h4 id="tos-12">12. Contact</h4>
                    <p>For questions about these terms, please visit our <a href="/contact">contact page</a>.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
