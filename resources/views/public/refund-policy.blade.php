@extends('layouts.public')

@section('title', 'Refund Policy — PolyTraderX')
@section('meta_description', 'Read the PolyTraderX Refund Policy for monthly, yearly, and lifetime plans, including eligibility windows, limitations, and refund request process.')

@section('content')
    <section class="ptx-section" style="padding-top: 140px;">
        <div class="container" style="max-width: 1000px;">
            <div class="mb-4">
                <h1 style="font-size: 2.5rem;">Refund Policy</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Last updated: {{ date('F j, Y') }}</p>
            </div>

            <div class="ptx-legal-layout">
                {{-- Table of Contents --}}
                <aside class="ptx-legal-toc">
                    <h6>Contents</h6>
                    <ul>
                        <li><a href="#overview">Overview</a></li>
                        <li><a href="#subscriptions">Subscriptions</a></li>
                        <li><a href="#lifetime">Lifetime Plans</a></li>
                        <li><a href="#additional">Additional Notes</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </aside>

                {{-- Content --}}
                <main class="ptx-legal-content">

                    <article id="overview">
                        <h2>Overview</h2>
                        <p>
                            PolyTraderX provides immediate access to compute-intensive strategy simulation and AI analysis tools.
                            Because access and usage begin instantly upon subscription activation, our refund policy is limited to
                            ensure fair use of system resources.
                        </p>
                        <p>
                            All refund requests are evaluated on a case-by-case basis. We reserve the right to deny refunds in cases
                            of abuse, excessive usage, or violation of our Terms of Service.
                        </p>
                    </article>

                    <article id="subscriptions">
                        <h2>Monthly & Yearly Subscriptions</h2>
                        <p>
                            <strong>24-Hour Window:</strong> Monthly and yearly subscriptions may be refunded within 24 hours of
                            purchase, provided there has been <strong>no AI usage</strong> (AI Muscles or AI Brain calls) during
                            that period.
                        </p>
                        <p>
                            <strong>After 24 Hours:</strong> No refunds are issued. All subscription payments are final.
                        </p>
                        <p>
                            <strong>Cancellations:</strong> You may cancel your subscription at any time to prevent future renewals.
                            However, canceling does not entitle you to a refund for the current billing period. You will retain access
                            until the end of your paid subscription term.
                        </p>
                        <div class="alert alert-info" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4); border-left: 4px solid #3b82f6; padding: 1.25rem; margin-top: 1rem; border-radius: 6px; color: #2563eb;">
                            <strong style="color: #1d4ed8; font-size: 1rem;"><i class="bi bi-info-circle me-2"></i>Example:</strong> If you subscribe on January 1st and use AI Muscles within the first hour,
                            you are <strong style="color: #1d4ed8;">not eligible</strong> for a refund. If you subscribe and do not use any AI features
                            within 24 hours, you <strong style="color: #1d4ed8;">may request</strong> a refund.
                        </div>
                    </article>

                    <article id="lifetime">
                        <h2>Lifetime (Early Bird) Plans</h2>
                        <p>
                            <strong>All lifetime plan purchases are non-refundable.</strong>
                        </p>
                        <p>
                            Lifetime plans provide one-time payment access to advanced features for the lifetime of the product
                            (not the lifetime of the user). Due to the significant discount and permanent access granted, no refunds
                            are issued under any circumstances.
                        </p>
                        <p>
                            Please ensure you understand the terms before purchasing a lifetime plan:
                        </p>
                        <ul>
                            <li>"Lifetime" refers to the lifetime of PolyTraderX as a product, not your personal lifetime</li>
                            <li>No guarantee of live trading execution (simulation-only platform)</li>
                            <li>Limited availability (few seats remaining)</li>
                            <li>Subject to our Terms of Service and acceptable use policies</li>
                        </ul>
                    </article>

                    <article id="additional">
                        <h2>Additional Notes</h2>

                        <h3>Partial Refunds</h3>
                        <p>
                            We <strong>do not offer partial refunds</strong> for unused portions of a subscription period.
                            If your subscription is monthly and you cancel after 2 weeks, you will not receive a pro-rated refund
                            for the remaining 2 weeks.
                        </p>

                        <h3>Abuse & Excessive Usage</h3>
                        <p>
                            Excessive or abusive usage of platform resources (AI calls, simulations, data exports) may void refund
                            eligibility, even within the 24-hour window. Examples include:
                        </p>
                        <ul>
                            <li>Running hundreds of AI analysis calls in a short period</li>
                            <li>Exporting large volumes of data with intent to cancel</li>
                            <li>Creating multiple accounts to exploit trial periods</li>
                            <li>Violating our Terms of Service or acceptable use policy</li>
                        </ul>

                        <h3>Refund Processing</h3>
                        <p>
                            Approved refunds are processed back to the original payment method used for purchase (cryptocurrency via NOWPayments).
                            Processing times vary:
                        </p>
                        <ul>
                            <li><strong>Cryptocurrency:</strong> 1-3 business days (subject to blockchain confirmation times)</li>
                            <li>Refunds are processed in the same cryptocurrency used for payment</li>
                            <li>We are not responsible for price fluctuations between purchase and refund dates</li>
                        </ul>

                        <h3>Chargebacks</h3>
                        <p>
                            <strong>Do not initiate a chargeback</strong> without first contacting our support team. Chargebacks
                            filed without attempting to resolve the issue with us may result in immediate account suspension and
                            potential legal action. We will dispute fraudulent chargebacks.
                        </p>
                    </article>

                    <article id="contact">
                        <h2>How to Request a Refund</h2>
                        <p>
                            To request a refund (if eligible), please contact us via:
                        </p>
                        <ul>
                            <li><strong>Email:</strong> support@polytraderx.com (or your configured support email)</li>
                            <li><strong>Include:</strong> Your account email, payment date, and reason for refund request</li>
                            <li><strong>Response Time:</strong> We aim to respond within 24-48 hours</li>
                        </ul>
                        <p>
                            All refund requests will be reviewed and you will receive a response indicating approval or denial
                            with reasoning.
                        </p>
                    </article>

                    <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); border-left: 4px solid #f59e0b; padding: 1.25rem; margin-top: 2rem; border-radius: 6px; color: #ea580c;">
                        <strong style="color: #c2410c; font-size: 1rem;"><i class="bi bi-exclamation-triangle me-2"></i>Important:</strong>
                        This refund policy is subject to change. Changes will be posted on this page with an updated "Last updated" date.
                        Continued use of PolyTraderX after policy changes constitutes acceptance of the new terms.
                    </div>

                </main>
            </div>
        </div>
    </section>
@endsection
