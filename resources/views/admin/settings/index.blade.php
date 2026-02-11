@extends('layouts.super-admin')

@section('title', 'Platform Settings')

@section('content')
<form method="POST" action="/admin/settings">
    @csrf

    @foreach($groups as $group => $settings)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">{{ ucfirst($group) }}</h6>
        </div>
        <div class="card-body">
            @foreach($settings as $setting)
            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-0">{{ $setting->key }}</label>
                    @if($setting->description)
                        <div class="text-muted small">{{ $setting->description }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    @if($setting->type === 'boolean')
                        <select name="settings[{{ $setting->key }}]" class="form-select form-select-sm">
                            <option value="true" {{ $setting->value === 'true' ? 'selected' : '' }}>True</option>
                            <option value="false" {{ $setting->value !== 'true' ? 'selected' : '' }}>False</option>
                        </select>
                    @else
                        <input type="text" name="settings[{{ $setting->key }}]" class="form-control form-control-sm" value="{{ $setting->value }}">
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

    <button type="submit" class="btn btn-primary">Save Settings</button>
</form>
@endsection
