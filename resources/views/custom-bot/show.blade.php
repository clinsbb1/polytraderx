@extends('layouts.admin')

@section('title', 'Custom Live Bot Build')

@section('content')

{{-- Header --}}
<div class="mb-4">
    <h4 style="color: var(--text-primary); font-weight: 700;">
        <i class="bi bi-robot me-2" style="color: var(--accent);"></i>Custom Polymarket Live Bot
        <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; vertical-align: middle;">Private Build</span>
    </h4>
    <p style="color: var(--text-secondary); max-width: 720px;">
        We can build a private, non-custodial automation bot for your strategy — separate from PolyTraderX simulation.
        <br><span style="font-size: 0.85rem;">Prefer to keep things simulation-only? <a href="{{ route('dashboard') }}" style="color: var(--accent);">Continue using PolyTraderX as normal.</a></span>
    </p>
</div>

{{-- Key Disclaimers --}}
<div class="alert mb-4" style="background: rgba(255,59,48,0.12); border: 2px solid rgba(255,59,48,0.5); border-radius: 10px; padding: 1.25rem;">
    <div class="d-flex align-items-start gap-2 mb-2">
        <i class="bi bi-exclamation-triangle-fill text-danger mt-1"></i>
        <strong style="color: var(--text-primary); font-size: 1rem;">Important Disclaimers — Please Read</strong>
    </div>
    <ul class="mb-0" style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.9;">
        <li><strong style="color: #ff3b30;">No profit guarantees.</strong> Past performance of any strategy does not guarantee future results.</li>
        <li><strong style="color: #ff3b30;">Non-custodial.</strong> We never hold, control, or access your funds. You retain full control of your Polymarket account.</li>
        <li><strong style="color: #ff3b30;">Separate service.</strong> This is a custom development engagement. PolyTraderX as a product remains simulation-only.</li>
        <li><strong style="color: #ff3b30;">Not financial advice.</strong> We implement your strategy rules. We do not advise on what to trade.</li>
    </ul>
</div>

{{-- Packages --}}
<div class="row g-4 mb-4">
    {{-- Basic --}}
    <div class="col-lg-4">
        <div class="ptx-card h-100">
            <div class="ptx-card-header">
                <h6 class="mb-0"><i class="bi bi-gear me-2" style="color: var(--accent);"></i>Basic Live Execution Bot</h6>
            </div>
            <div class="ptx-card-body">
                <div class="mb-3">
                    <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">$2,500 – $4,000</span>
                    <span style="color: var(--text-secondary); font-size: 0.85rem;"> one-time</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.85rem;" class="mb-3">Starting range. Final quote depends on scope.</p>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    <div class="mb-2" style="color: var(--accent); font-weight: 600;">Included</div>
                    <ul class="mb-3" style="padding-left: 1.2rem; line-height: 1.9;">
                        <li>Polymarket integration + order execution</li>
                        <li>Secure EIP-712 signing (client-controlled keys)</li>
                        <li>Your strategy rules implemented</li>
                        <li>Risk controls (max bet, daily loss, position limit)</li>
                        <li>Logging + basic dashboard</li>
                        <li>Deployment guide + VPS setup assistance</li>
                    </ul>
                    <div class="mb-2" style="color: #ff3b30; font-weight: 600;">Not Included</div>
                    <ul style="padding-left: 1.2rem; line-height: 1.9; color: var(--text-secondary);">
                        <li>AI automation layer</li>
                        <li>Strategy creation or advice</li>
                        <li>Ongoing optimization</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- AI-Enhanced --}}
    <div class="col-lg-4">
        <div class="ptx-card h-100" style="border-color: var(--accent); border-width: 2px;">
            <div class="ptx-card-header" style="background: rgba(0,230,118,0.08);">
                <h6 class="mb-0">
                    <i class="bi bi-cpu me-2" style="color: var(--accent);"></i>AI-Enhanced Automation Bot
                    <span class="badge ms-2" style="background: var(--accent); color: #000; font-size: 0.65rem;">Popular</span>
                </h6>
            </div>
            <div class="ptx-card-body">
                <div class="mb-3">
                    <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">$5,000 – $9,000</span>
                    <span style="color: var(--text-secondary); font-size: 0.85rem;"> one-time</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.85rem;" class="mb-3">Everything in Basic, plus:</p>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    <div class="mb-2" style="color: var(--accent); font-weight: 600;">Additional Inclusions</div>
                    <ul style="padding-left: 1.2rem; line-height: 1.9;">
                        <li>AI signal scoring / confidence layer (budget governed)</li>
                        <li>Post-trade analysis + reporting</li>
                        <li>Parameter tuning suggestions (manual approval required)</li>
                        <li>Advanced monitoring + alerts</li>
                    </ul>
                    <div class="mt-3 p-2" style="background: rgba(0,230,118,0.08); border-radius: 6px; font-size: 0.8rem;">
                        <i class="bi bi-info-circle me-1" style="color: var(--accent);"></i>
                        AI usage costs (Anthropic API) are separate and billed to your own API key.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Retainer --}}
    <div class="col-lg-4">
        <div class="ptx-card h-100">
            <div class="ptx-card-header">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2" style="color: #ffc107;"></i>Ongoing Support Retainer</h6>
            </div>
            <div class="ptx-card-body">
                <div class="mb-3">
                    <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">$500 – $1,500</span>
                    <span style="color: var(--text-secondary); font-size: 0.85rem;"> /month</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.85rem;" class="mb-3">Optional. Add after the initial build.</p>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    <div class="mb-2" style="color: #ffc107; font-weight: 600;">Included</div>
                    <ul style="padding-left: 1.2rem; line-height: 1.9;">
                        <li>Monitoring + bug fixes</li>
                        <li>Strategy tweaks (defined monthly scope)</li>
                        <li>Monthly performance review</li>
                        <li>Infrastructure + model updates</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- How It Works + Requirements --}}
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="ptx-card h-100">
            <div class="ptx-card-header">
                <h6 class="mb-0"><i class="bi bi-list-ol me-2" style="color: var(--accent);"></i>How It Works</h6>
            </div>
            <div class="ptx-card-body">
                @php
                $steps = [
                    ['Apply', 'Submit your requirements using the form below.'],
                    ['Review call', 'We confirm scope, feasibility, and ask clarifying questions.'],
                    ['Quote + timeline', 'Fixed scope with milestone-based delivery.'],
                    ['Build + test', 'Development with staging / paper mode first where possible.'],
                    ['Deploy + handover', 'Full documentation and access transfer to you.'],
                    ['Optional retainer', 'Ongoing monitoring and improvements if desired.'],
                ];
                @endphp
                <ol class="mb-0" style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.9; padding-left: 1.4rem;">
                    @foreach($steps as $step)
                    <li class="mb-1">
                        <strong style="color: var(--text-primary);">{{ $step[0] }}</strong> — {{ $step[1] }}
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="ptx-card h-100">
            <div class="ptx-card-header">
                <h6 class="mb-0"><i class="bi bi-check2-square me-2" style="color: #ffc107;"></i>Come Prepared With</h6>
            </div>
            <div class="ptx-card-body">
                <ul class="mb-0" style="color: var(--text-secondary); font-size: 0.85rem; line-height: 2; padding-left: 1.2rem;">
                    <li>Your target Polymarket market types</li>
                    <li>Strategy rules (entry/exit/filters/timing)</li>
                    <li>Risk limits (max bet, daily loss cap)</li>
                    <li>Preferred hosting setup</li>
                    <li>Whether you want an AI layer</li>
                    <li>Expected trade frequency / volume</li>
                    <li>Your technical comfort level (hands-on vs managed)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert mb-4" style="background: rgba(0,230,118,0.12); border: 1px solid rgba(0,230,118,0.4); border-radius: 10px; color: var(--text-primary);">
    <i class="bi bi-check-circle-fill me-2" style="color: var(--accent);"></i>{{ session('success') }}
</div>
@endif

{{-- Request Status Banner (shown when there is an active or declined request) --}}
@if($activeRequest)
@php
    $statusConfig = [
        'pending' => [
            'icon'    => 'bi-hourglass-split',
            'color'   => '#ffc107',
            'bg'      => 'rgba(255,193,7,0.10)',
            'border'  => 'rgba(255,193,7,0.4)',
            'title'   => 'Request submitted — awaiting review',
            'body'    => 'We have received your request and will review it within 2–3 business days. You will hear from us via email.',
        ],
        'reviewing' => [
            'icon'    => 'bi-eye-fill',
            'color'   => '#0dcaf0',
            'bg'      => 'rgba(13,202,240,0.10)',
            'border'  => 'rgba(13,202,240,0.4)',
            'title'   => 'Your request is being reviewed',
            'body'    => 'Our team is actively evaluating your submission. We may reach out with follow-up questions before providing a quote.',
        ],
        'accepted' => [
            'icon'    => 'bi-check-circle-fill',
            'color'   => 'var(--accent)',
            'bg'      => 'rgba(0,230,118,0.10)',
            'border'  => 'rgba(0,230,118,0.4)',
            'title'   => 'Request accepted — project proceeding',
            'body'    => 'Great news! We have accepted your request and will be in touch shortly to align on scope, timeline, and kick-off.',
        ],
    ];
    $cfg = $statusConfig[$activeRequest->status] ?? $statusConfig['pending'];
@endphp
<div class="ptx-card mb-4" style="border-color: {{ $cfg['border'] }}; border-width: 2px;">
    <div class="ptx-card-body">
        <div class="d-flex align-items-start gap-3">
            <i class="bi {{ $cfg['icon'] }} fs-4 mt-1 flex-shrink-0" style="color: {{ $cfg['color'] }};"></i>
            <div class="flex-grow-1">
                <div class="fw-bold mb-1" style="color: var(--text-primary);">{{ $cfg['title'] }}</div>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">{{ $cfg['body'] }}</div>

                {{-- Summary of what they submitted --}}
                <div class="mt-3 p-3 rounded" style="background: rgba(255,255,255,0.04); border: 1px solid var(--border-subtle); font-size: 0.82rem; color: var(--text-secondary);">
                    <div class="row g-2">
                        <div class="col-sm-4"><span class="text-muted">Budget:</span> {{ $activeRequest->budget_range ?: '—' }}</div>
                        <div class="col-sm-4"><span class="text-muted">Timeline:</span> {{ $activeRequest->timeline ?: '—' }}</div>
                        <div class="col-sm-4"><span class="text-muted">AI layer:</span> {{ $activeRequest->wants_ai ? 'Yes' : 'No' }}</div>
                        <div class="col-sm-4"><span class="text-muted">Submitted:</span> {{ $activeRequest->created_at->diffForHumans() }}</div>
                        @if($activeRequest->markets)
                        <div class="col-sm-8"><span class="text-muted">Markets:</span> {{ $activeRequest->markets }}</div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 small" style="color: var(--text-secondary);">
                    Questions? Email <a href="mailto:support@polytraderx.xyz" style="color: var(--accent);">support@polytraderx.xyz</a>
                    or DM <a href="https://x.com/polytraderx" target="_blank" rel="noopener noreferrer" style="color: var(--accent);">@polytraderx on X</a>.
                </div>
            </div>
        </div>
    </div>
</div>

@elseif($declinedRequest)
{{-- Declined — show a notice, then let them re-apply --}}
<div class="ptx-card mb-4" style="border-color: rgba(255,59,48,0.4); border-width: 2px;">
    <div class="ptx-card-body">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-x-circle-fill fs-4 mt-1 flex-shrink-0" style="color: #ff3b30;"></i>
            <div>
                <div class="fw-bold mb-1" style="color: var(--text-primary);">Previous request was not accepted</div>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    Your last request (submitted {{ $declinedRequest->created_at->diffForHumans() }}) was not taken on at this time.
                    You are welcome to submit a new request below — perhaps with an updated strategy or different scope.
                </div>
                <div class="mt-2 small" style="color: var(--text-secondary);">
                    Have questions about the decision? Email <a href="mailto:support@polytraderx.xyz" style="color: var(--accent);">support@polytraderx.xyz</a>.
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Request Form --}}
@if(!$activeRequest)
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h6 class="mb-0">
            <i class="bi bi-send me-2" style="color: var(--accent);"></i>
            {{ $declinedRequest ? 'Submit a New Request' : 'Apply for a Custom Bot Build' }}
        </h6>
    </div>
    <div class="ptx-card-body">

        @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('custom-bot.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="ptx-input" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Email</label>
                    <input type="text" class="ptx-input" value="{{ auth()->user()->email }}" disabled>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px;">We'll use your account email.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Telegram or WhatsApp <span style="color: var(--text-secondary);">(optional)</span></label>
                    <input type="text" name="contact" value="{{ old('contact') }}" class="ptx-input" placeholder="@username or +234...">
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Market Types to Trade <span style="color: var(--text-secondary);">(optional)</span></label>
                    <input type="text" name="markets" value="{{ old('markets') }}" class="ptx-input" placeholder="e.g. BTC/ETH 5-min, crypto categories">
                </div>
                <div class="col-12">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Strategy Summary <span class="text-danger">*</span></label>
                    <textarea name="strategy_summary" class="ptx-input" rows="4" required placeholder="Describe your entry/exit rules, filters, timing logic, and any conditions. Be as specific as possible.">{{ old('strategy_summary') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Timeframe</label>
                    <select name="timeframe" class="ptx-input">
                        <option value="">Select...</option>
                        <option value="5min" {{ old('timeframe') === '5min' ? 'selected' : '' }}>5-minute markets</option>
                        <option value="15min" {{ old('timeframe') === '15min' ? 'selected' : '' }}>15-minute markets</option>
                        <option value="both" {{ old('timeframe') === 'both' ? 'selected' : '' }}>Both 5min + 15min</option>
                        <option value="other" {{ old('timeframe') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Max Bet per Trade (USDC)</label>
                    <input type="number" name="max_bet" value="{{ old('max_bet') }}" class="ptx-input" min="0" step="1" placeholder="e.g. 50">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Daily Loss Limit (USDC)</label>
                    <input type="number" name="daily_loss" value="{{ old('daily_loss') }}" class="ptx-input" min="0" step="1" placeholder="e.g. 200">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">AI Automation Layer? <span class="text-danger">*</span></label>
                    <select name="wants_ai" class="ptx-input" required>
                        <option value="">Select...</option>
                        <option value="yes" {{ old('wants_ai') === 'yes' ? 'selected' : '' }}>Yes — AI-Enhanced build</option>
                        <option value="no" {{ old('wants_ai') === 'no' ? 'selected' : '' }}>No — Basic execution only</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Budget Range <span class="text-danger">*</span></label>
                    <select name="budget_range" class="ptx-input" required>
                        <option value="">Select...</option>
                        <option value="under_2500" {{ old('budget_range') === 'under_2500' ? 'selected' : '' }}>Under $2,500</option>
                        <option value="2500_4000" {{ old('budget_range') === '2500_4000' ? 'selected' : '' }}>$2,500 – $4,000</option>
                        <option value="5000_9000" {{ old('budget_range') === '5000_9000' ? 'selected' : '' }}>$5,000 – $9,000</option>
                        <option value="10000_plus" {{ old('budget_range') === '10000_plus' ? 'selected' : '' }}>$10,000+</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Timeline Urgency <span class="text-danger">*</span></label>
                    <select name="timeline" class="ptx-input" required>
                        <option value="">Select...</option>
                        <option value="asap" {{ old('timeline') === 'asap' ? 'selected' : '' }}>ASAP (within 2 weeks)</option>
                        <option value="1_month" {{ old('timeline') === '1_month' ? 'selected' : '' }}>Within 1 month</option>
                        <option value="flexible" {{ old('timeline') === 'flexible' ? 'selected' : '' }}>Flexible (no rush)</option>
                        <option value="exploring" {{ old('timeline') === 'exploring' ? 'selected' : '' }}>Just exploring options</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 0.85rem;">Additional Notes / Links <span style="color: var(--text-secondary);">(optional)</span></label>
                    <textarea name="notes" class="ptx-input" rows="3" placeholder="Any other context, links to your strategy docs, or questions.">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="disclaimer" id="disclaimer" class="form-check-input" value="1" {{ old('disclaimer') ? 'checked' : '' }} required>
                        <label for="disclaimer" class="form-check-label" style="color: var(--text-secondary); font-size: 0.85rem;">
                            I understand there are <strong>no profit guarantees</strong>, PolyTraderX does <strong>not custody funds</strong>, and this is a custom development service with no guaranteed outcomes. <span class="text-danger">*</span>
                        </label>
                    </div>
                </div>
                <div class="col-12 pt-2">
                    <button type="submit" class="btn-ptx-primary">
                        <i class="bi bi-send me-2"></i>Submit Request
                    </button>
                    <span class="ms-3" style="color: var(--text-secondary); font-size: 0.8rem;">Not a purchase. We'll review and respond within 2–3 business days.</span>
                </div>
            </div>
        </form>

    </div>
</div>
@endif

{{-- FAQs --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h6 class="mb-0"><i class="bi bi-question-circle me-2" style="color: var(--accent);"></i>Frequently Asked Questions</h6>
    </div>
    <div class="ptx-card-body p-0">
        <div class="accordion accordion-flush" id="faqAccordion">
            @php
            $faqs = [
                [
                    'q' => 'Do you guarantee profits?',
                    'a' => 'No. We build automation infrastructure based on your rules. Performance depends entirely on your strategy and market conditions. No past result implies future results.',
                ],
                [
                    'q' => 'Do you hold or control my funds?',
                    'a' => 'No. The bot operates non-custodially. You retain full control of your Polymarket account and wallet at all times. We never have access to your private keys or funds.',
                ],
                [
                    'q' => 'Does PolyTraderX do live trading?',
                    'a' => 'No. PolyTraderX is simulation-only. Live bot builds are a completely separate custom development service offered only to clients who specifically request it.',
                ],
                [
                    'q' => 'Do you create the strategy for me?',
                    'a' => 'For basic builds, you provide the rules and we implement them. For AI-enhanced builds, we can add analysis layers — but we still do not provide financial advice or tell you what to trade.',
                ],
                [
                    'q' => 'What ongoing costs should I expect?',
                    'a' => 'Hosting/VPS costs (typically $20–$60/month), optional AI API usage costs if enabled (billed to your own key), and the optional monthly support retainer if desired.',
                ],
                [
                    'q' => 'How long does it take?',
                    'a' => 'Scope-dependent. After reviewing your request we provide a timeline with milestones. A basic build typically takes 2–4 weeks. AI-enhanced builds may take 4–8 weeks.',
                ],
                [
                    'q' => 'How do I get started?',
                    'a' => 'Submit the request form above, or message us directly at support@polytraderx.xyz or DM @polytraderx on X.',
                ],
            ];
            @endphp
            @foreach($faqs as $i => $faq)
            <div class="accordion-item" style="background: transparent; border-color: var(--border-subtle);">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faq{{ $i }}"
                        style="background: transparent; color: var(--text-primary); font-size: 0.9rem; box-shadow: none;"
                    >
                        {{ $faq['q'] }}
                    </button>
                </h2>
                <div id="faq{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="color: var(--text-secondary); font-size: 0.9rem;">
                        {{ $faq['a'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Contact Options --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h6 class="mb-0"><i class="bi bi-envelope me-2" style="color: var(--accent);"></i>Contact Us Directly</h6>
    </div>
    <div class="ptx-card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <a href="mailto:support@polytraderx.xyz" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle); border-radius: 8px;">
                    <i class="bi bi-envelope-fill" style="font-size: 1.5rem; color: var(--accent);"></i>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 0.9rem;">Email</div>
                        <div style="color: var(--text-secondary); font-size: 0.82rem;">support@polytraderx.xyz</div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="https://x.com/polytraderx" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle); border-radius: 8px;">
                    <i class="bi bi-twitter-x" style="font-size: 1.5rem; color: var(--text-primary);"></i>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 0.9rem;">DM on X</div>
                        <div style="color: var(--text-secondary); font-size: 0.82rem;">@polytraderx</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
