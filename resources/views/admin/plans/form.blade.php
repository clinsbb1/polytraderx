@extends('layouts.super-admin')

@section('title', $plan ? 'Edit Plan' : 'Create Plan')

@section('content')
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="{{ $plan ? '/admin/plans/' . $plan->id : '/admin/plans' }}">
            @csrf
            @if($plan) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Slug</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $plan->slug ?? '') }}" required>
                @error('slug') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name ?? '') }}" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Price (USD)</label>
                    <input type="number" name="price_usd" class="form-control" step="0.01" value="{{ old('price_usd', $plan->price_usd ?? '0') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Billing Period</label>
                    <select name="billing_period" class="form-select">
                        <option value="monthly" {{ old('billing_period', $plan->billing_period ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_period', $plan->billing_period ?? '') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Max Daily Trades</label>
                    <input type="number" name="max_daily_trades" class="form-control" value="{{ old('max_daily_trades', $plan->max_daily_trades ?? '') }}" placeholder="0 = unlimited">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Max Concurrent Positions</label>
                    <input type="number" name="max_concurrent_positions" class="form-control" value="{{ old('max_concurrent_positions', $plan->max_concurrent_positions ?? '') }}" placeholder="0 = unlimited">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Trial Days</label>
                <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $plan->trial_days ?? '0') }}">
            </div>

            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="has_ai_muscles" value="1" {{ old('has_ai_muscles', $plan->has_ai_muscles ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">AI Muscles (Haiku)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="has_ai_brain" value="1" {{ old('has_ai_brain', $plan->has_ai_brain ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">AI Brain (Sonnet)</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">Active</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/admin/plans" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $plan ? 'Update' : 'Create' }} Plan</button>
            </div>
        </form>
    </div>
</div>
@endsection
