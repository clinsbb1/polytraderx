{{-- Shared FAQ accordion component --}}
<div class="accordion ptx-accordion" id="faqAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                What do I need to get started?
            </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">You need a Polymarket account with API keys (API Key, Secret, Passphrase, and Wallet Address). That's it! AI is provided by the platform, and Telegram notifications can be set up with one command. No Anthropic key or private keys required.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                Is there a risk of losing money?
            </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Yes. While the strategy aims for high-confidence trades, prediction markets carry inherent risk. API desyncs, unexpected market moves, or technical failures can result in losses. Always use paper trading mode first and never trade with money you cannot afford to lose.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                How do I pay?
            </button>
        </h2>
        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">We accept cryptocurrency payments via NOWPayments. You can pay with BTC, ETH, USDC, and many other cryptocurrencies. A 7-day free trial is available to get started.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                Do you store my private keys?
            </button>
        </h2>
        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">No. We never ask for or store your private keys. We only need your Polymarket API credentials (API Key, Secret, and Passphrase) which provide limited trading access. Your funds remain in your Polymarket wallet at all times.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                How does the 3-tier AI work?
            </button>
        </h2>
        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Tier 1 (Reflexes) is free logic that scans markets every minute. Tier 2 (Muscles) uses Claude Haiku every 5 minutes for confidence scoring. Tier 3 (Brain) uses Claude Sonnet on-demand for post-loss analysis and strategy review. This tiered approach optimizes AI costs.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                Can I customize the trading strategy?
            </button>
        </h2>
        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Yes! All strategy parameters are fully customizable: confidence thresholds, bet sizes, daily trade limits, entry window timing, monitored assets, notification preferences, and more. Every change is logged for audit purposes.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                How do Telegram notifications work?
            </button>
        </h2>
        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">We have a platform-wide Telegram bot. After registering, go to Settings > Telegram, copy your Account ID, and send it to our bot. You'll receive real-time trade notifications, daily summaries, and alerts — all configurable.</div>
        </div>
    </div>
</div>
