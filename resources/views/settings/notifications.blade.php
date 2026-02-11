@extends('layouts.admin')

@section('title', 'Notification Settings')

@section('content')
<h1 class="h3 mb-4">Notification Settings</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="/settings/notifications">
            @csrf

            @foreach($notifications as $param)
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <label class="form-label fw-semibold mb-0">{{ $param->key }}</label>
                        @if($param->description)
                            <div class="text-muted small">{{ $param->description }}</div>
                        @endif
                    </div>
                    <div>
                        @if($param->type === 'boolean')
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="params[{{ $param->key }}]" value="true"
                                    {{ in_array(strtolower($param->value), ['true', '1', 'yes']) ? 'checked' : '' }}>
                            </div>
                        @else
                            <input type="text" name="params[{{ $param->key }}]" class="form-control form-control-sm" style="width:120px" value="{{ $param->value }}">
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Save Notifications</button>
        </form>
    </div>
</div>
@endsection
