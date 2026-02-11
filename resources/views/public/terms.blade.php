@extends('layouts.public')

@section('title', 'Terms of Service — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:800px">
        <h1 class="fw-bold mb-4">Terms of Service</h1>
        <p class="text-muted small">Last updated: {{ date('F j, Y') }}</p>

        <h4 class="mt-4">1. Acceptance of Terms</h4>
        <p>By accessing and using PolyTraderX ("the Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, do not use the Service.</p>

        <h4 class="mt-4">2. Description of Service</h4>
        <p>PolyTraderX is an automated trading bot for Polymarket prediction markets. The Service connects to your Polymarket account using API keys you provide and executes trades based on algorithmic and AI-driven strategies.</p>

        <h4 class="mt-4">3. Risk Disclaimer</h4>
        <p><strong>IMPORTANT:</strong> Trading on prediction markets involves significant risk of loss. Past performance does not guarantee future results. You acknowledge that:</p>
        <ul>
            <li>You may lose some or all of your invested capital</li>
            <li>Automated trading systems can malfunction, experience API desyncs, or make incorrect decisions</li>
            <li>Market conditions can change rapidly and unpredictably</li>
            <li>You are solely responsible for all trading decisions and their outcomes</li>
            <li>PolyTraderX is not a financial advisor and does not provide financial advice</li>
        </ul>

        <h4 class="mt-4">4. User Responsibilities</h4>
        <p>You are responsible for:</p>
        <ul>
            <li>Securing your API keys and account credentials</li>
            <li>Configuring appropriate risk management parameters</li>
            <li>Monitoring your account and trading activity</li>
            <li>Compliance with applicable laws and regulations in your jurisdiction</li>
            <li>Only trading with funds you can afford to lose</li>
        </ul>

        <h4 class="mt-4">5. API Keys & Security</h4>
        <p>Your API keys are stored encrypted on our servers. We never share your keys with third parties. However, you acknowledge that no system is completely secure and assume the risk of any unauthorized access.</p>

        <h4 class="mt-4">6. Subscription & Payments</h4>
        <p>Subscription payments are processed via NOWPayments in cryptocurrency. Payments are non-refundable once confirmed on the blockchain. Subscriptions may be cancelled at any time, effective at the end of the current billing period.</p>

        <h4 class="mt-4">7. Limitation of Liability</h4>
        <p>To the maximum extent permitted by law, PolyTraderX shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or trading capital.</p>

        <h4 class="mt-4">8. Termination</h4>
        <p>We reserve the right to terminate or suspend your account at any time for violation of these terms or for any other reason at our sole discretion.</p>

        <h4 class="mt-4">9. Changes to Terms</h4>
        <p>We may update these terms from time to time. Continued use of the Service after changes constitutes acceptance of the new terms.</p>

        <h4 class="mt-4">10. Contact</h4>
        <p>For questions about these terms, please visit our <a href="/contact">contact page</a>.</p>
    </div>
</section>
@endsection
