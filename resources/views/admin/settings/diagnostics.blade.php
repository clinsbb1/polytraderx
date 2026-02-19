@extends('layouts.super-admin')

@section('title', 'Service Diagnostics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Service Diagnostics</h5>
    <div class="d-flex gap-2">
        <a href="/admin/settings/diagnostics" class="btn btn-primary btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Run Again
        </a>
        <a href="/admin/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Settings
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-telegram me-2"></i>Telegram</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted mb-2">Bot token configured</div>
                <div class="mb-2">{{ $telegram['bot_token_configured'] ? 'Yes' : 'No' }}</div>
                <div class="small text-muted mb-2">Webhook secret configured</div>
                <div class="mb-2">{{ $telegram['webhook_secret_configured'] ? 'Yes' : 'No' }}</div>
                <div class="small text-muted mb-2">getMe</div>
                <div class="mb-2">{{ is_null($telegram['get_me_ok']) ? '(not run)' : ($telegram['get_me_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">Webhook check</div>
                <div class="mb-2">{{ is_null($telegram['get_webhook_info_ok']) ? '(not run)' : ($telegram['get_webhook_info_ok'] ? 'OK' : 'Failed') }}</div>
                @if($telegram['last_error_message'])
                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">{{ $telegram['last_error_message'] }}</div>
                @endif
                @if($telegram['http_error'])
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">{{ $telegram['http_error'] }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Polymarket Public Data</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted mb-2">Base URL</div>
                <div class="mb-2"><code>{{ $polymarket['base_url'] }}</code></div>
                <div class="small text-muted mb-2">Gamma URL</div>
                <div class="mb-2"><code>{{ $polymarket['gamma_url'] }}</code></div>
                <div class="small text-muted mb-2">/time endpoint</div>
                <div class="mb-2">{{ is_null($polymarket['time_ok']) ? '(not run)' : ($polymarket['time_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">/markets endpoint</div>
                <div class="mb-2">{{ is_null($polymarket['markets_ok']) ? '(not run)' : ($polymarket['markets_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">Active markets parsed</div>
                <div class="mb-2">{{ is_null($polymarket['markets_count']) ? '(n/a)' : number_format((int) $polymarket['markets_count']) }}</div>
                <div class="small text-muted mb-2">Gamma /markets endpoint</div>
                <div class="mb-2">{{ is_null($polymarket['gamma_markets_ok']) ? '(not run)' : ($polymarket['gamma_markets_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">Gamma active markets parsed</div>
                <div class="mb-2">{{ is_null($polymarket['gamma_markets_count']) ? '(n/a)' : number_format((int) $polymarket['gamma_markets_count']) }}</div>
                <div class="small text-muted mb-2">Strict filter source (Crypto 5M/15M)</div>
                <div class="mb-2">{{ $polymarket['strict_source'] ?? '(n/a)' }}</div>
                <div class="small text-muted mb-2">Strict accepted / rejected</div>
                <div class="mb-2">
                    @if(is_null($polymarket['strict_normalized_count']) || is_null($polymarket['strict_rejected_count']))
                        (n/a)
                    @else
                        {{ number_format((int) $polymarket['strict_normalized_count']) }} / {{ number_format((int) $polymarket['strict_rejected_count']) }}
                    @endif
                </div>
                @if($polymarket['http_error'])
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">{{ $polymarket['http_error'] }}</div>
                @endif
                @if($polymarket['strict_http_error'])
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">{{ $polymarket['strict_http_error'] }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-cpu me-2"></i>Anthropic</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted mb-2">API key configured</div>
                <div class="mb-2">{{ $anthropic['api_key_configured'] ? 'Yes' : 'No' }}</div>
                <div class="small text-muted mb-2">/models check</div>
                <div class="mb-2">{{ is_null($anthropic['models_ok']) ? '(not run)' : ($anthropic['models_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">Models returned (not account balance)</div>
                <div class="mb-2">{{ is_null($anthropic['models_count']) ? '(n/a)' : number_format((int) $anthropic['models_count']) }}</div>
                <div class="small text-muted mb-2">Inference probe (/messages)</div>
                <div class="mb-2">
                    @if(is_null($anthropic['inference_ok']))
                        (not run)
                    @else
                        {{ $anthropic['inference_ok'] ? 'OK' : 'Failed' }}
                    @endif
                </div>
                <div class="small text-muted mb-2">Credits status</div>
                <div class="mb-2">{{ strtoupper((string) ($anthropic['credits_status'] ?? 'unknown')) }}</div>
                @if($anthropic['credit_pause_active'])
                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                        AI calls are temporarily paused: {{ $anthropic['credit_pause_reason'] ?? 'insufficient credits detected' }}
                    </div>
                @endif
                @if($anthropic['inference_error'])
                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">{{ $anthropic['inference_error'] }}</div>
                @endif
                @if($anthropic['error'])
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">{{ $anthropic['error'] }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Cloudflare Turnstile</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted mb-2">Enabled</div>
                <div class="mb-2">{{ $turnstile['enabled'] ? 'Yes' : 'No' }}</div>
                <div class="small text-muted mb-2">Site key configured</div>
                <div class="mb-2">{{ $turnstile['site_key_configured'] ? 'Yes' : 'No' }}@if($turnstile['site_key_preview']) <span class="text-muted">({{ $turnstile['site_key_preview'] }})</span>@endif</div>
                <div class="small text-muted mb-2">Secret key configured</div>
                <div class="mb-2">{{ $turnstile['secret_key_configured'] ? 'Yes' : 'No' }}</div>
                <div class="small text-muted mb-2">Verify endpoint check</div>
                <div class="mb-2">
                    @if(is_null($turnstile['verify_endpoint_ok']))
                        (not run)
                    @else
                        {{ $turnstile['verify_endpoint_ok'] ? 'OK' : 'Failed' }}
                    @endif
                </div>
                <div class="small text-muted mb-2">Secret validity</div>
                <div class="mb-2">
                    @if(is_null($turnstile['secret_valid']))
                        Unknown
                    @else
                        {{ $turnstile['secret_valid'] ? 'Valid' : 'Invalid' }}
                    @endif
                </div>
                @if(!empty($turnstile['error_codes']))
                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                        Error codes: {{ implode(', ', $turnstile['error_codes']) }}
                    </div>
                @endif
                @if($turnstile['error'])
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">{{ $turnstile['error'] }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Polymarket Strict Filter Diagnostics (Crypto 5M/15M Only)</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-3">
                <div class="small text-muted">Raw markets fetched</div>
                <div class="fw-semibold">{{ is_null($polymarket['strict_raw_count']) ? '(n/a)' : number_format((int) $polymarket['strict_raw_count']) }}</div>
            </div>
            <div class="col-lg-3">
                <div class="small text-muted">Accepted (normalized)</div>
                <div class="fw-semibold">{{ is_null($polymarket['strict_normalized_count']) ? '(n/a)' : number_format((int) $polymarket['strict_normalized_count']) }}</div>
            </div>
            <div class="col-lg-3">
                <div class="small text-muted">Rejected</div>
                <div class="fw-semibold">{{ is_null($polymarket['strict_rejected_count']) ? '(n/a)' : number_format((int) $polymarket['strict_rejected_count']) }}</div>
            </div>
            <div class="col-lg-3">
                <div class="small text-muted">HTTP status (gamma / clob)</div>
                <div class="fw-semibold">{{ $polymarket['strict_gamma_status'] ?? '-' }} / {{ $polymarket['strict_clob_status'] ?? '-' }}</div>
            </div>
        </div>

        <hr>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="small text-muted mb-2">Duration breakdown</div>
                <div class="small">
                    5min: {{ number_format((int) ($polymarket['strict_duration_breakdown']['5min'] ?? 0)) }}<br>
                    15min: {{ number_format((int) ($polymarket['strict_duration_breakdown']['15min'] ?? 0)) }}
                </div>
            </div>
            <div class="col-lg-4">
                <div class="small text-muted mb-2">Asset breakdown</div>
                <div class="small">
                    BTC: {{ number_format((int) ($polymarket['strict_asset_breakdown']['BTC'] ?? 0)) }}<br>
                    ETH: {{ number_format((int) ($polymarket['strict_asset_breakdown']['ETH'] ?? 0)) }}<br>
                    SOL: {{ number_format((int) ($polymarket['strict_asset_breakdown']['SOL'] ?? 0)) }}<br>
                    XRP: {{ number_format((int) ($polymarket['strict_asset_breakdown']['XRP'] ?? 0)) }}
                </div>
            </div>
            <div class="col-lg-4">
                <div class="small text-muted mb-2">Top rejection reasons</div>
                @if(!empty($polymarket['strict_rejection_breakdown']))
                    <div class="small">
                        @foreach($polymarket['strict_rejection_breakdown'] as $reason => $count)
                            <div>{{ $reason }}: {{ number_format((int) $count) }}</div>
                        @endforeach
                    </div>
                @else
                    <div class="small text-muted">(none)</div>
                @endif
            </div>
        </div>

        <hr>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="small text-muted mb-2">Accepted sample markets</div>
                @if(!empty($polymarket['strict_accepted_samples']))
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Duration</th>
                                    <th>Seconds Left</th>
                                    <th>Slug</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($polymarket['strict_accepted_samples'] as $sample)
                                    <tr>
                                        <td>{{ $sample['asset'] ?? '-' }}</td>
                                        <td>{{ $sample['duration'] ?? '-' }}</td>
                                        <td>{{ isset($sample['seconds_remaining']) ? number_format((int) $sample['seconds_remaining']) : '-' }}</td>
                                        <td><code>{{ $sample['slug'] ?? '-' }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="small text-muted">(none)</div>
                @endif
            </div>
            <div class="col-lg-6">
                <div class="small text-muted mb-2">Rejected sample markets</div>
                @if(!empty($polymarket['strict_rejected_samples']))
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th>Slug</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($polymarket['strict_rejected_samples'] as $sample)
                                    <tr>
                                        <td>{{ $sample['reason'] ?? '-' }}</td>
                                        <td><code>{{ $sample['slug'] ?? '-' }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="small text-muted">(none)</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="small text-muted mt-3">
    Checked {{ $checkedAt->diffForHumans() }}.
</div>
@endsection
