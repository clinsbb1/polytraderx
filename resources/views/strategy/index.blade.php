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
                                @if($param->type === 'boolean')
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
