@extends('layouts.admin')

@section('title', 'Notification Settings')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">Notification Settings</h4>

<div class="ptx-card" style="max-width:600px">
    <div class="ptx-card-body">
        <form method="POST" action="/settings/notifications">
            @csrf

            @foreach($notifications as $param)
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <label class="ptx-label mb-0">{{ $param->key }}</label>
                        @if($param->description)
                            <div style="color: var(--text-secondary); font-size: 0.8rem;">{{ $param->description }}</div>
                        @endif
                    </div>
                    <div>
                        @if($param->type === 'boolean')
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="params[{{ $param->key }}]" value="true"
                                    {{ in_array(strtolower($param->value), ['true', '1', 'yes']) ? 'checked' : '' }}>
                            </div>
                        @else
                            <input type="text" name="params[{{ $param->key }}]" class="ptx-input ptx-input-sm" style="width:120px" value="{{ $param->value }}">
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            <button type="submit" class="btn-ptx-primary btn-ptx-sm">Save Notifications</button>
        </form>
    </div>
</div>
@endsection
