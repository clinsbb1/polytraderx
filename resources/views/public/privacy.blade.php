@extends('layouts.public')

@section('title', 'Privacy Policy — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:800px">
        <h1 class="fw-bold mb-4">Privacy Policy</h1>
        <p class="text-muted small">Last updated: {{ date('F j, Y') }}</p>

        <h4 class="mt-4">1. Information We Collect</h4>
        <p>We collect information you provide directly:</p>
        <ul>
            <li><strong>Account Information:</strong> Name, email address, timezone, Account ID (auto-generated PTX-XXXXXXXXXXXX)</li>
            <li><strong>Polymarket API Credentials:</strong> API Key, API Secret, API Passphrase, and Wallet Address (stored encrypted). We do <strong>not</strong> collect or store private keys.</li>
            <li><strong>Telegram Chat ID:</strong> Stored when you link your Telegram account via our platform bot (optional)</li>
            <li><strong>Trading Data:</strong> Trade history, P&L records, balance snapshots</li>
            <li><strong>Usage Data:</strong> Bot activity logs, AI decision records, strategy parameter changes</li>
        </ul>

        <h4 class="mt-4">2. How We Use Your Information</h4>
        <p>We use your information to:</p>
        <ul>
            <li>Operate the trading bot on your behalf using your Polymarket API credentials</li>
            <li>Display your dashboard, trade history, and analytics</li>
            <li>Send notifications via our platform Telegram bot (if you link your account)</li>
            <li>Run AI analysis (Claude Haiku/Sonnet) on market data to generate trade signals</li>
            <li>Perform post-loss forensic audits to improve strategy</li>
            <li>Process subscription payments</li>
        </ul>

        <h4 class="mt-4">3. Data Storage & Security</h4>
        <p>All API credentials (Polymarket API Key, Secret, and Passphrase) are encrypted at rest using Laravel's encryption facilities. We use industry-standard security practices to protect your data. Your trading data is stored in a secure database. We never store your Polymarket private keys — your funds remain in your own Polymarket wallet at all times.</p>

        <h4 class="mt-4">4. Third-Party Services</h4>
        <p>We integrate with the following third-party services:</p>
        <ul>
            <li><strong>Polymarket:</strong> For executing trades (using your API credentials — API Key, Secret, Passphrase only)</li>
            <li><strong>Binance:</strong> For real-time crypto price feeds (public API, no user credentials needed)</li>
            <li><strong>Anthropic (Claude AI):</strong> For AI market analysis and forensics (platform-provided API key, not yours)</li>
            <li><strong>Telegram:</strong> For sending notifications via our platform bot (your Telegram Chat ID is stored if you link your account)</li>
            <li><strong>NOWPayments:</strong> For processing cryptocurrency subscription payments</li>
            <li><strong>Google:</strong> For OAuth authentication (optional)</li>
        </ul>

        <h4 class="mt-4">5. What We Do NOT Collect</h4>
        <p>We want to be clear about what we do <strong>not</strong> collect or store:</p>
        <ul>
            <li>Polymarket private keys or wallet seed phrases</li>
            <li>Anthropic API keys (AI is provided by the platform)</li>
            <li>Binance API keys (we use public price feeds only)</li>
            <li>Personal Telegram bot tokens (we use a single platform bot)</li>
            <li>Financial information beyond what's needed for crypto payments</li>
        </ul>

        <h4 class="mt-4">6. Data Retention</h4>
        <p>We retain your trading data for as long as your account is active. Upon account deletion, your data will be permanently removed within 30 days. API credentials are deleted immediately upon account closure.</p>

        <h4 class="mt-4">7. Your Rights</h4>
        <p>You have the right to:</p>
        <ul>
            <li>Access your personal data via your dashboard</li>
            <li>Correct inaccurate data via your settings</li>
            <li>Request deletion of your data and account</li>
            <li>Export your trading data</li>
            <li>Unlink your Telegram account at any time</li>
            <li>Withdraw consent for data processing</li>
        </ul>

        <h4 class="mt-4">8. Cookies</h4>
        <p>We use essential cookies for session management and authentication. No third-party tracking cookies are used unless you have Google Analytics enabled (configurable by platform administrators).</p>

        <h4 class="mt-4">9. Changes to This Policy</h4>
        <p>We may update this privacy policy from time to time. We will notify you of significant changes via email or dashboard notification.</p>

        <h4 class="mt-4">10. Contact</h4>
        <p>For privacy-related inquiries, please visit our <a href="/contact">contact page</a>.</p>
    </div>
</section>
@endsection
