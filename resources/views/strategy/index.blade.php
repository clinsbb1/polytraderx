@extends('layouts.admin')

@section('title', 'Strategy Parameters')

@section('content')
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle"></i> All parameters control simulation behavior. No live trades are executed.
</div>

@if(!($telegramLinked ?? false))
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Link Telegram in <a href="{{ url('/settings/telegram') }}">Settings</a> before enabling the simulator.
</div>
@endif

{{-- Getting Started Guide --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header" style="cursor: pointer;" onclick="document.getElementById('strategyGuideContent').classList.toggle('d-none')">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-book me-2" style="color: var(--accent);"></i> Strategy Configuration Guide</h5>
            <i class="bi bi-chevron-down ms-3"></i>
        </div>
    </div>
    <div class="ptx-card-body d-none" id="strategyGuideContent">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <h6 style="color: var(--accent); border-bottom: 2px solid var(--accent); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                    <i class="bi bi-shield-check me-2"></i>Risk Management
                </h6>
                <ul style="color: var(--text-secondary); font-size: 0.9rem;">
                    <li class="mb-2">
                        <strong>MAX_BET_AMOUNT:</strong> Maximum $ to risk on a single trade. Start with $5-10 for testing.
                    </li>
                    <li class="mb-2">
                        <strong>MAX_BET_PERCENTAGE:</strong> Max % of your balance per trade (e.g., 10% of $100 = $10 max bet).
                    </li>
                    <li class="mb-2">
                        <strong>MAX_DAILY_LOSS:</strong> Stop trading for the day after losing this amount. Protects against bad streaks.
                    </li>
                    <li class="mb-2">
                        <strong>MAX_DAILY_TRADES:</strong> Limit total trades per day. Prevents overtrading.
                    </li>
                    <li>
                        <strong>MAX_CONCURRENT_POSITIONS:</strong> How many open trades allowed at once. Keep low (2-3) initially.
                    </li>
                </ul>

                <h6 style="color: var(--profit); border-bottom: 2px solid var(--profit); padding-bottom: 0.5rem; margin-bottom: 1rem; margin-top: 1.5rem;">
                    <i class="bi bi-graph-up me-2"></i>Trading Rules
                </h6>
                <ul style="color: var(--text-secondary); font-size: 0.9rem;">
                    <li class="mb-2">
                        <strong>SIMULATOR_ENABLED:</strong> Master on/off switch. Set to <span class="ptx-badge ptx-badge-success">true</span> to start.
                    </li>
                    <li class="mb-2">
                        <strong>MIN_CONFIDENCE_SCORE:</strong> AI must be this confident (0.0-1.0) to place a trade. Higher = fewer but safer trades.
                    </li>
                    <li class="mb-2">
                        <strong>MIN_ENTRY_PRICE_THRESHOLD:</strong> Only buy the "likely winner" side if price is ≥ 0.92 (92% probability).
                    </li>
                    <li class="mb-2">
                        <strong>ENTRY_WINDOW_SECONDS:</strong> Only enter trades in the final X seconds before market close. Default: 60s.
                    </li>
                    <li class="mb-2">
                        <strong>PRICE_FEED_SOURCE:</strong> Select where simulation price context comes from. Default is Binance.
                    </li>
                    <li class="mb-2">
                        <strong>MONITORED_ASSETS:</strong> Which cryptos to trade (BTC, ETH, SOL, XRP). Select from checkboxes.
                    </li>
                    <li>
                        <strong>MARKET_DURATIONS:</strong> Trade 5-minute markets, 15-minute markets, or both.
                    </li>
                </ul>
            </div>

            <div class="col-lg-6">
                <h6 style="color: #ffc107; border-bottom: 2px solid #ffc107; padding-bottom: 0.5rem; margin-bottom: 1rem; margin-top: 1.5rem;">
                    <i class="bi bi-bell me-2"></i>Notifications
                </h6>
                <ul style="color: var(--text-secondary); font-size: 0.9rem;">
                    <li class="mb-2">
                        <strong>NOTIFY_DAILY_PNL:</strong> Get a daily summary via Telegram (requires <a href="/settings/telegram">Telegram link</a>).
                    </li>
                    <li class="mb-2">
                        <strong>NOTIFY_EACH_TRADE:</strong> Real-time notifications for every trade (can be noisy).
                    </li>
                    <li class="mb-2">
                        <strong>LOW_BALANCE_THRESHOLD:</strong> Alert when simulated balance drops below this amount.
                    </li>
                    <li>
                        <strong>DRAWDOWN_ALERT_PERCENTAGE:</strong> Alert when daily loss exceeds this % of starting balance.
                    </li>
                </ul>

                <div class="alert alert-success mt-3" style="background: rgba(0,230,118,0.15); border: 2px solid rgba(0,230,118,0.5); border-radius: 8px; padding: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="bi bi-check-circle-fill" style="color: #00e676; font-size: 1.25rem;"></i>
                        <strong style="color: var(--text-primary); font-size: 1rem;">Recommended Starting Settings:</strong>
                    </div>
                    <ul class="mb-0" style="font-size: 0.9rem; color: var(--text-primary); line-height: 1.8;">
                        <li><strong>SIMULATOR_ENABLED:</strong> true</li>
                        <li><strong>MAX_BET_AMOUNT:</strong> $5-10</li>
                        <li><strong>MIN_CONFIDENCE_SCORE:</strong> 0.92-0.95</li>
                        <li><strong>MARKET_DURATIONS:</strong> Both checked</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="alert alert-warning mb-0 mt-3" style="background: rgba(255,193,7,0.2); border: 2px solid rgba(255,193,7,0.6); border-radius: 8px; padding: 1rem;">
            <div style="display: flex; align-items: start; gap: 0.75rem;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #ffc107; font-size: 1.25rem; margin-top: 2px;"></i>
                <div style="color: var(--text-primary); font-size: 0.95rem; line-height: 1.6;">
                    <strong style="font-size: 1rem;">Important:</strong> After changing parameters, the simulator will use the new values on the next run (every minute).
                    Monitor the <a href="{{ route('logs.index') }}" style="color: #ffc107; font-weight: 600; text-decoration: underline;">Logs</a> to see when changes take effect.
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $groupLabels = [
        'risk' => 'Risk Management',
        'trading' => 'Trading Rules',
        'notifications' => 'Notification Preferences',
    ];
@endphp

@foreach($groups as $groupKey => $params)
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>{{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}</h5>
    </div>
    <div class="ptx-card-body p-0">
        <form method="POST" action="{{ route('strategy.update', $groupKey) }}">
            @csrf
            <div class="table-responsive">
                <table class="ptx-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Value</th>
                            <th>Description</th>
                            <th>Last Updated By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($params as $param)
                        <tr>
                            <td><code>{{ $param->key }}</code></td>
                            <td>
                                @if($param->key === 'MONITORED_ASSETS')
                                    @php
                                        $selectedAssets = array_map('trim', explode(',', $param->value));
                                    @endphp
                                    <div style="display: flex; gap: 1rem;">
                                        <label style="color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" name="assets[]" value="BTC" {{ in_array('BTC', $selectedAssets) ? 'checked' : '' }} style="cursor: pointer;">
                                            <span>BTC</span>
                                        </label>
                                        <label style="color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" name="assets[]" value="ETH" {{ in_array('ETH', $selectedAssets) ? 'checked' : '' }} style="cursor: pointer;">
                                            <span>ETH</span>
                                        </label>
                                        <label style="color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" name="assets[]" value="SOL" {{ in_array('SOL', $selectedAssets) ? 'checked' : '' }} style="cursor: pointer;">
                                            <span>SOL</span>
                                        </label>
                                        <label style="color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" name="assets[]" value="XRP" {{ in_array('XRP', $selectedAssets) ? 'checked' : '' }} style="cursor: pointer;">
                                            <span>XRP</span>
                                        </label>
                                    </div>
                                    <input type="hidden" name="params[{{ $param->key }}]" id="monitored_assets_input" value="{{ $param->value }}">
                                @elseif($param->key === 'MARKET_DURATIONS')
                                    @php
                                        $selectedDurations = array_map('trim', explode(',', $param->value));
                                    @endphp
                                    <div style="display: flex; gap: 1rem;">
                                        <label style="color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" name="durations[]" value="5min" {{ in_array('5min', $selectedDurations) ? 'checked' : '' }} style="cursor: pointer;">
                                            <span>5-minute markets</span>
                                        </label>
                                        <label style="color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" name="durations[]" value="15min" {{ in_array('15min', $selectedDurations) ? 'checked' : '' }} style="cursor: pointer;">
                                            <span>15-minute markets</span>
                                        </label>
                                    </div>
                                    <input type="hidden" name="params[{{ $param->key }}]" id="market_durations_input" value="{{ $param->value }}">
                                @elseif($param->key === 'PRICE_FEED_SOURCE')
                                    @php
                                        $currentSource = strtolower(trim((string) $param->value));
                                    @endphp
                                    <select name="params[{{ $param->key }}]" class="ptx-input ptx-input-sm" style="width:220px">
                                        <option value="binance" {{ $currentSource === 'binance' ? 'selected' : '' }}>Binance (Default)</option>
                                        <option value="coingecko" {{ $currentSource === 'coingecko' ? 'selected' : '' }}>CoinGecko</option>
                                        <option value="coinbase" {{ $currentSource === 'coinbase' ? 'selected' : '' }}>Coinbase</option>
                                        <option value="kraken" {{ $currentSource === 'kraken' ? 'selected' : '' }}>Kraken</option>
                                    </select>
                                @elseif($param->key === 'SIMULATOR_ENABLED')
                                    <select name="params[{{ $param->key }}]" class="ptx-input ptx-input-sm" style="width:140px">
                                        <option
                                            value="true"
                                            {{ $param->value === 'true' ? 'selected' : '' }}
                                            {{ !($telegramLinked ?? false) ? 'disabled' : '' }}
                                        >
                                            true
                                        </option>
                                        <option value="false" {{ $param->value === 'false' ? 'selected' : '' }}>false</option>
                                    </select>
                                    @if(!($telegramLinked ?? false))
                                        <div class="mt-1" style="font-size: 0.75rem; color: #ffc107;">
                                            Telegram required to enable simulator.
                                        </div>
                                    @endif
                                @elseif($param->type === 'boolean')
                                    <select name="params[{{ $param->key }}]" class="ptx-input ptx-input-sm" style="width:100px">
                                        <option value="true" {{ $param->value === 'true' ? 'selected' : '' }}>true</option>
                                        <option value="false" {{ $param->value === 'false' ? 'selected' : '' }}>false</option>
                                    </select>
                                @else
                                    <input type="text" name="params[{{ $param->key }}]" value="{{ $param->value }}" class="ptx-input ptx-input-sm" style="width:150px">
                                @endif
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $param->description }}</td>
                            <td>
                                <span class="ptx-badge ptx-badge-{{ $param->updated_by === 'admin' ? 'primary' : ($param->updated_by === 'ai' ? 'info' : 'secondary') }}">
                                    {{ $param->updated_by === 'admin' ? 'You' : ucfirst($param->updated_by) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 pb-4 pt-2">
                <button type="submit" class="btn-ptx-primary btn-ptx-sm">Save {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle monitored assets checkboxes
    const assetCheckboxes = document.querySelectorAll('input[name="assets[]"]');
    const assetHiddenInput = document.getElementById('monitored_assets_input');

    if (assetCheckboxes.length > 0 && assetHiddenInput) {
        assetCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = Array.from(assetCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // At least one must be selected
                if (checked.length === 0) {
                    this.checked = true;
                    alert('At least one asset must be selected.');
                    return;
                }

                assetHiddenInput.value = checked.join(',');
            });
        });
    }

    // Handle market duration checkboxes
    const durationCheckboxes = document.querySelectorAll('input[name="durations[]"]');
    const durationHiddenInput = document.getElementById('market_durations_input');

    if (durationCheckboxes.length > 0 && durationHiddenInput) {
        durationCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = Array.from(durationCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // At least one must be selected
                if (checked.length === 0) {
                    this.checked = true;
                    alert('At least one market duration must be selected.');
                    return;
                }

                durationHiddenInput.value = checked.join(',');
            });
        });
    }
});
</script>
@endpush
