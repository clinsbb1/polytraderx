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
                <div class="small text-muted mb-2">/time endpoint</div>
                <div class="mb-2">{{ is_null($polymarket['time_ok']) ? '(not run)' : ($polymarket['time_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">/markets endpoint</div>
                <div class="mb-2">{{ is_null($polymarket['markets_ok']) ? '(not run)' : ($polymarket['markets_ok'] ? 'OK' : 'Failed') }}</div>
                <div class="small text-muted mb-2">Active markets parsed</div>
                <div class="mb-2">{{ is_null($polymarket['markets_count']) ? '(n/a)' : number_format((int) $polymarket['markets_count']) }}</div>
                @if($polymarket['http_error'])
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">{{ $polymarket['http_error'] }}</div>
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
                <div class="small text-muted mb-2">Models returned</div>
                <div class="mb-2">{{ is_null($anthropic['models_count']) ? '(n/a)' : number_format((int) $anthropic['models_count']) }}</div>
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

<div class="small text-muted mt-3">
    Checked {{ $checkedAt->diffForHumans() }}.
</div>
@endsection
