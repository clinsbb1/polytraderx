@extends('layouts.admin')

@section('title', 'Strategy Parameters')

@section('content')
@php
    $groupLabels = [
        'risk' => 'Risk Management',
        'trading' => 'Trading Rules',
        'ai' => 'AI Settings',
        'notifications' => 'Notification Preferences',
    ];
@endphp

@foreach($groups as $groupKey => $params)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">{{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('strategy.update', $groupKey) }}">
            @csrf
            <table class="table table-sm">
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
                            @if($param->type === 'boolean')
                                <select name="params[{{ $param->key }}]" class="form-select form-select-sm" style="width:100px">
                                    <option value="true" {{ $param->value === 'true' ? 'selected' : '' }}>true</option>
                                    <option value="false" {{ $param->value === 'false' ? 'selected' : '' }}>false</option>
                                </select>
                            @else
                                <input type="text" name="params[{{ $param->key }}]" value="{{ $param->value }}" class="form-control form-control-sm" style="width:150px">
                            @endif
                        </td>
                        <td class="text-muted small">{{ $param->description }}</td>
                        <td><span class="badge bg-secondary">{{ $param->updated_by }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary btn-sm">Save {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}</button>
        </form>
    </div>
</div>
@endforeach
@endsection
