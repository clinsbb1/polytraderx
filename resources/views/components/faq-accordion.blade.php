{{-- Shared FAQ accordion component --}}
<div class="accordion ptx-accordion" id="faqAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq0">
                Why do I need PolyTraderX?
            </button>
        </h2>
        <div id="faq0" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
                <p class="mb-3">Most Polymarket traders rely on intuition, scattered charts, or hindsight. PolyTraderX gives you a structured way to turn ideas into tested strategies—without risking real money.</p>
                <p class="mb-3"><strong>PolyTraderX helps you:</strong></p>
                <ul class="mb-3">
                    <li>Simulate strategies on real Polymarket market data and live price feeds</li>
                    <li>Understand what actually works by tracking drawdowns, consistency, and stability—not just wins</li>
                    <li>See why strategies fail through detailed analytics and AI-assisted analysis</li>
                    <li>Test ideas safely before committing real capital</li>
                    <li>Build discipline by removing emotion and guesswork from decision-making</li>
                </ul>
                <p class="mb-0">Instead of asking "Did I win today?", PolyTraderX helps you answer "Is this strategy sound over time?" It doesn't place real trades or promise profits. It gives you clarity, insight, and confidence—so when you do trade, you do it with intention.</p>
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                How realistic are the simulations?
            </button>
        </h2>
        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Very realistic. We use real-time Polymarket market data and Binance spot prices. The simulator runs the exact same logic a live bot would use: market scanning, AI confidence scoring, risk management, and position tracking. The only difference is that orders aren't sent to Polymarket. Think of it as a flight simulator — full realism, zero risk.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq0b">
                What are signals?
            </button>
        </h2>
        <div id="faq0b" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
                Signals are trade opportunities identified by the simulator based on your strategy parameters. Each signal represents a moment when market conditions meet your criteria—like reaching a specific confidence threshold in the final seconds of a prediction market. The simulator evaluates these signals using AI analysis and real-time price data, then simulates how the trade would perform. Your plan determines how many signals can be generated per day.
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                What is PolyTraderX?
            </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">PolyTraderX is a strategy simulation platform for Polymarket's crypto prediction markets. You design trading strategies with customizable parameters, and we test them against real market data in real-time. All activity is simulated — no live trades are placed. It's the perfect environment to learn, iterate, and refine strategies without risking real money.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Does PolyTraderX trade real money?
            </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body"><strong>No. All activity is simulated.</strong> PolyTraderX generates trading signals and tracks simulated positions using real Polymarket market data, but no actual trades are placed. No real money is at risk. This is a learning and strategy development platform.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                Does PolyTraderX offer live trading bots?
            </button>
        </h2>
        <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">No. PolyTraderX is a simulation-only strategy lab. It does not execute live trades or connect to your wallet. The platform is designed to help you test, analyze, and understand strategies using real market data—without risking real money.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11">
                Why doesn’t PolyTraderX support live bots right now?
            </button>
        </h2>
        <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
                <p class="mb-3">Live trading introduces real financial risk, legal complexity, and responsibility. Most strategies fail not because of execution, but because they haven’t been properly tested.</p>
                <p class="mb-3">PolyTraderX focuses on simulation first so you can:</p>
                <ul class="mb-3">
                    <li>identify overfitting</li>
                    <li>understand drawdowns and stability</li>
                    <li>refine strategies with data, not emotion</li>
                </ul>
                <p class="mb-0">This approach leads to better decisions and avoids the pitfalls of rushing into live execution.</p>
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq12">
                What if I want a custom live trading bot anyway?
            </button>
        </h2>
        <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
                <p class="mb-3">If you already have a well-tested strategy and want a private, custom live bot, you can reach out to us directly. Custom bots are built outside the PolyTraderX platform and handled on a case-by-case basis.</p>
                <p class="mb-2">You can contact us via:</p>
                <ul class="mb-3">
                    <li>the <a href="/contact">Contact Us</a> page</li>
                    <li>X (Twitter): <a href="https://x.com/polytraderx" target="_blank" rel="noopener noreferrer"><strong>@polytraderx</strong></a></li>
                </ul>
                <p class="mb-0">We’ll let you know if it’s a good fit and what the next steps would look like.</p>
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                Will live trading be supported in the future?
            </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Possibly. Our focus is on perfecting the simulation experience first. If there's demand and it makes sense strategically, we may add optional live execution in the future. For now, simulation-only ensures you can safely learn and test strategies.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                What do I need to get started?
            </button>
        </h2>
        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Just sign up and start designing your strategy. No API keys required for simulation mode. You can configure all strategy parameters (confidence thresholds, position sizes, monitored assets, etc.) from the dashboard. AI analysis is provided by the platform. Optional: Connect Telegram for real-time notifications.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                How does the 3-tier AI work?
            </button>
        </h2>
        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Tier 1 (Reflexes) is free rule-based logic that scans markets every minute. Tier 2 (Muscles) uses Claude Haiku every 5 minutes for quick confidence scoring. Tier 3 (Brain) uses Claude Sonnet on-demand for deep forensic analysis after losing signals and daily/weekly strategy reviews. This tiered approach optimizes AI costs while maintaining intelligence.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                Can I customize the strategy?
            </button>
        </h2>
        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Yes! All strategy parameters are fully customizable: confidence thresholds, position sizes, daily signal limits, entry window timing, monitored assets (BTC/ETH/SOL), notification preferences, and more. Every change is logged for audit purposes. The platform is designed for experimentation.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                How do Telegram notifications work?
            </button>
        </h2>
        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">We have a platform-wide Telegram bot. After registering, go to Settings > Telegram, copy your Account ID, and send it to our bot. You'll receive real-time signal notifications, daily P&L summaries, AI audit results, and error alerts — all configurable based on your preferences.</div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                How do I pay?
            </button>
        </h2>
        <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">We accept cryptocurrency payments via NOWPayments. You can pay with BTC, ETH, USDC, USDT, SOL, and over 100 other cryptocurrencies. A 7-day free trial is available with no payment required. No credit card needed.</div>
        </div>
    </div>
</div>
