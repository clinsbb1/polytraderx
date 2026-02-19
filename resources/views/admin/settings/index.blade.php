@extends('layouts.super-admin')

@section('title', 'Platform Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Platform Settings</h5>
    <div class="d-flex align-items-center gap-2">
        <a href="/admin/settings/diagnostics" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-activity me-1"></i>Service Diagnostics
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="fw-semibold">AI Audit Recharge Marker</div>
            <div class="small text-muted">
                @if($aiAuditRechargedAt !== '')
                    Current marker: {{ $aiAuditRechargedAt }}
                @else
                    Not set. Loss audits are currently skipped.
                @endif
            </div>
        </div>
        <form method="POST" action="/admin/settings/ai-recharged-now" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-warning">
                <i class="bi bi-lightning-charge me-1"></i>Mark AI Recharged Now
            </button>
        </form>
    </div>
</div>

<form method="POST" action="/admin/settings">
    @csrf

    @foreach($groups as $group => $settings)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-folder me-2"></i>{{ ucwords(str_replace('_', ' ', $group)) }}
            </h6>
        </div>
        <div class="card-body">
            @foreach($settings as $setting)
            <div class="row mb-3 align-items-start">
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-0" for="setting-{{ $setting->key }}">
                        {{ $setting->key }}
                    </label>
                    @if($setting->description)
                        <div class="text-muted small">{{ $setting->description }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    @if($setting->type === 'boolean')
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="settings[{{ $setting->key }}]" value="true"
                                id="setting-{{ $setting->key }}"
                                {{ $setting->value === 'true' || $setting->value === '1' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="setting-{{ $setting->key }}">
                                {{ $setting->value === 'true' || $setting->value === '1' ? 'Enabled' : 'Disabled' }}
                            </label>
                        </div>
                    @elseif((str_contains(strtolower($setting->key), 'secret') || str_contains(strtolower($setting->key), 'key') || str_contains(strtolower($setting->key), 'password') || str_contains(strtolower($setting->key), 'token')) && !str_contains(strtolower($setting->key), 'nowpayments') && strtolower($setting->key) !== 'telegram_bot_token' && strtolower($setting->key) !== 'anthropic_api_key')
                        <input type="password" name="settings[{{ $setting->key }}]"
                            class="form-control form-control-sm"
                            id="setting-{{ $setting->key }}"
                            value="{{ $setting->value }}"
                            autocomplete="off">
                    @elseif($setting->type === 'integer' || $setting->type === 'numeric' || $setting->type === 'number')
                        <input type="number" name="settings[{{ $setting->key }}]"
                            class="form-control form-control-sm"
                            id="setting-{{ $setting->key }}"
                            value="{{ $setting->value }}"
                            step="any">
                    @else
                        <input type="text" name="settings[{{ $setting->key }}]"
                            class="form-control form-control-sm"
                            id="setting-{{ $setting->key }}"
                            value="{{ $setting->value }}">
                    @endif
                </div>
                <div class="col-md-2">
                    <span class="badge bg-secondary">{{ $setting->type }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-2"></i>Save All Settings
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    // Toggle switch labels dynamically
    document.querySelectorAll('.form-check-input[type="checkbox"][role="switch"]').forEach(function(el) {
        el.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (label) {
                label.textContent = this.checked ? 'Enabled' : 'Disabled';
            }
        });
    });
</script>
@endsection
