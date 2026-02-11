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
            <li><strong>Account Information:</strong> Name, email address, timezone</li>
            <li><strong>API Credentials:</strong> Polymarket, Telegram, Anthropic, and Binance API keys (stored encrypted)</li>
            <li><strong>Trading Data:</strong> Trade history, P&L records, balance snapshots</li>
            <li><strong>Usage Data:</strong> Bot activity logs, AI decision records</li>
        </ul>

        <h4 class="mt-4">2. How We Use Your Information</h4>
        <p>We use your information to:</p>
        <ul>
            <li>Operate the trading bot on your behalf</li>
            <li>Display your dashboard and trading analytics</li>
            <li>Send notifications via Telegram (if configured)</li>
            <li>Improve the trading strategy through AI analysis</li>
            <li>Process subscription payments</li>
        </ul>

        <h4 class="mt-4">3. Data Storage & Security</h4>
        <p>All API keys are encrypted at rest using Laravel's encryption facilities. We use industry-standard security practices to protect your data. Your trading data is stored in a secure MySQL database.</p>

        <h4 class="mt-4">4. Third-Party Services</h4>
        <p>We integrate with the following third-party services:</p>
        <ul>
            <li><strong>Polymarket:</strong> For executing trades (using your API keys)</li>
            <li><strong>Binance:</strong> For real-time crypto price feeds</li>
            <li><strong>Anthropic (Claude):</strong> For AI market analysis (using your API key)</li>
            <li><strong>Telegram:</strong> For sending notifications (using your bot token)</li>
            <li><strong>NOWPayments:</strong> For processing cryptocurrency payments</li>
            <li><strong>Google:</strong> For OAuth authentication (optional)</li>
        </ul>

        <h4 class="mt-4">5. Data Retention</h4>
        <p>We retain your trading data for as long as your account is active. Upon account deletion, your data will be permanently removed within 30 days. API keys are deleted immediately upon account closure.</p>

        <h4 class="mt-4">6. Your Rights</h4>
        <p>You have the right to:</p>
        <ul>
            <li>Access your personal data</li>
            <li>Correct inaccurate data</li>
            <li>Request deletion of your data</li>
            <li>Export your trading data</li>
            <li>Withdraw consent for data processing</li>
        </ul>

        <h4 class="mt-4">7. Cookies</h4>
        <p>We use essential cookies for session management and authentication. No third-party tracking cookies are used.</p>

        <h4 class="mt-4">8. Changes to This Policy</h4>
        <p>We may update this privacy policy from time to time. We will notify you of significant changes via email or dashboard notification.</p>

        <h4 class="mt-4">9. Contact</h4>
        <p>For privacy-related inquiries, please visit our <a href="/contact">contact page</a>.</p>
    </div>
</section>
@endsection
