@extends('layouts.super-admin')

@section('title', $plan ? 'Edit Plan: ' . $plan->name : 'Create Plan')

@section('content')
<div class="mb-3">
    <a href="/admin/plans" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Plans
    </a>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-header">
        <h6 class="mb-0">{{ $plan ? 'Edit Plan' : 'Create New Plan' }}</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $plan ? '/admin/plans/' . $plan->id : '/admin/plans' }}">
            @csrf
            @if($plan) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $plan->slug ?? '') }}" required placeholder="e.g. basic, pro">
                @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Unique identifier for this plan. Use lowercase, no spaces.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name ?? '') }}" required placeholder="e.g. Basic Plan">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Price (USD) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="price_usd" class="form-control @error('price_usd') is-invalid @enderror" step="0.01" min="0" value="{{ old('price_usd', $plan->price_usd ?? '0') }}" required>
                    </div>
                    @error('price_usd') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Billing Period <span class="text-danger">*</span></label>
                    <select name="billing_period" class="form-select @error('billing_period') is-invalid @enderror">
                        <option value="monthly" {{ old('billing_period', $plan->billing_period ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_period', $plan->billing_period ?? '') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                    @error('billing_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Max Daily Trades</label>
                    <input type="number" name="max_signals_per_day" class="form-control @error('max_signals_per_day') is-invalid @enderror" min="0" value="{{ old('max_signals_per_day', $plan->max_signals_per_day ?? '') }}" placeholder="0 = unlimited">
                    @error('max_signals_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Max Concurrent Positions</label>
                    <input type="number" name="max_concurrent_positions" class="form-control @error('max_concurrent_positions') is-invalid @enderror" min="0" value="{{ old('max_concurrent_positions', $plan->max_concurrent_positions ?? '') }}" placeholder="0 = unlimited">
                    @error('max_concurrent_positions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Trial Days</label>
                <input type="number" name="trial_days" class="form-control @error('trial_days') is-invalid @enderror" min="0" value="{{ old('trial_days', $plan->trial_days ?? '0') }}">
                @error('trial_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Number of free trial days for new subscribers.</div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Lifetime Cap</label>
                    <input type="number" name="lifetime_cap" class="form-control @error('lifetime_cap') is-invalid @enderror" min="0" value="{{ old('lifetime_cap', $plan->lifetime_cap ?? '') }}" placeholder="0 = unlimited">
                    @error('lifetime_cap') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Maximum lifetime memberships allowed for this plan.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Lifetime Sold</label>
                    <input type="number" name="lifetime_sold" class="form-control @error('lifetime_sold') is-invalid @enderror" min="0" value="{{ old('lifetime_sold', $plan->lifetime_sold ?? '0') }}">
                    @error('lifetime_sold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Number of lifetime memberships already sold.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Sort Order</label>
                <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" min="0" value="{{ old('sort_order', $plan->sort_order ?? '0') }}">
                @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Lower numbers appear first on the pricing page.</div>
            </div>

            <hr>
            <h6 class="mb-3">AI Limits</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Muscles Calls/Day</label>
                    <input type="number" name="max_ai_muscles_calls_per_day" class="form-control" min="0" value="{{ old('max_ai_muscles_calls_per_day', $plan->max_ai_muscles_calls_per_day ?? '') }}" placeholder="0 = unlimited">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Brain Calls/Day</label>
                    <input type="number" name="max_ai_brain_calls_per_day" class="form-control" min="0" value="{{ old('max_ai_brain_calls_per_day', $plan->max_ai_brain_calls_per_day ?? '') }}" placeholder="0 = unlimited">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Brain Calls/Month</label>
                    <input type="number" name="max_ai_brain_calls_per_month" class="form-control" min="0" value="{{ old('max_ai_brain_calls_per_month', $plan->max_ai_brain_calls_per_month ?? '') }}" placeholder="0 = unlimited">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold d-block">AI Features Enabled</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="ai_muscles_enabled" value="1" id="aiMuscles"
                        {{ old('ai_muscles_enabled', $plan->ai_muscles_enabled ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="aiMuscles">AI Muscles (Haiku)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="ai_brain_enabled" value="1" id="aiBrain"
                        {{ old('ai_brain_enabled', $plan->ai_brain_enabled ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="aiBrain">AI Brain (Sonnet)</label>
                </div>
            </div>

            <hr>
            <h6 class="mb-3">Feature Access</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Historical Data (Days)</label>
                    <input type="number" name="historical_days" class="form-control" min="0" value="{{ old('historical_days', $plan->historical_days ?? '7') }}">
                    <div class="form-text">How many days of historical data users can access</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold d-block">Features Enabled</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="csv_export_enabled" value="1" id="csvExport"
                        {{ old('csv_export_enabled', $plan->csv_export_enabled ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="csvExport">CSV Export</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="strategy_health_metrics" value="1" id="strategyHealth"
                        {{ old('strategy_health_metrics', $plan->strategy_health_metrics ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="strategyHealth">Strategy Health Metrics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="telegram_enabled" value="1" id="telegram"
                        {{ old('telegram_enabled', $plan->telegram_enabled ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="telegram">Telegram Notifications</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="priority_processing" value="1" id="priority"
                        {{ old('priority_processing', $plan->priority_processing ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="priority">Priority Processing</label>
                </div>
            </div>

            <hr>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                        {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
                <div class="form-text">Inactive plans are hidden from the pricing page.</div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/admin/plans" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>{{ $plan ? 'Update Plan' : 'Create Plan' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
