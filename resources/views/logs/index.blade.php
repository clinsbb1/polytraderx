@extends('layouts.admin')

@section('title', 'Trade Logs')

@section('content')
{{-- Filter Bar --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-body">
        <form method="GET" action="{{ route('logs.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Trade ID or data..." class="ptx-input ptx-input-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Event Type</label>
                <select name="event" class="ptx-input ptx-input-sm">
                    <option value="">All Events</option>
                    <option value="placed" @selected(request('event') === 'placed')>Placed</option>
                    <option value="filled" @selected(request('event') === 'filled')>Filled</option>
                    <option value="resolved" @selected(request('event') === 'resolved')>Resolved</option>
                    <option value="error" @selected(request('event') === 'error')>Error</option>
                    <option value="price_snapshot" @selected(request('event') === 'price_snapshot')>Price Snapshot</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="ptx-input ptx-input-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="ptx-input ptx-input-sm">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn-ptx-primary btn-ptx-sm w-100">Filter</button>
            </div>
            @if(request()->hasAny(['search', 'event', 'from', 'to']))
                <div class="col-md-1">
                    <a href="{{ route('logs.index') }}" class="btn-ptx-secondary btn-ptx-sm w-100 d-inline-block text-center">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Logs Table --}}
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Trade Logs</h5>
        <span style="color: var(--text-secondary); font-size: 0.85rem;">{{ $logs->total() }} entries</span>
    </div>
    <div class="ptx-card-body p-0">
        @if($logs->count() > 0)
            <table class="ptx-table">
                <thead>
                    <tr>
                        <th style="width: 30px;"></th>
                        <th>Timestamp</th>
                        <th>Trade ID</th>
                        <th>Event</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr data-bs-toggle="collapse" data-bs-target="#log-detail-{{ $log->id }}" style="cursor: pointer;">
                            <td><i class="bi bi-chevron-down" style="font-size: 0.7rem; color: var(--text-secondary);"></i></td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $log->created_at?->format('M d, H:i:s') }}</td>
                            <td>
                                @if($log->trade_id)
                                    <a href="{{ route('trades.show', $log->trade_id) }}" onclick="event.stopPropagation();" style="color: var(--accent);">#{{ $log->trade_id }}</a>
                                @else
                                    <span style="color: var(--text-secondary);">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $eventBadge = match($log->event) {
                                        'placed' => 'primary',
                                        'filled' => 'success',
                                        'resolved' => 'info',
                                        'error' => 'danger',
                                        'price_snapshot' => 'secondary',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="ptx-badge ptx-badge-{{ $eventBadge }}">{{ $log->event }}</span>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                {{ Str::limit(is_array($log->data) ? json_encode($log->data) : ($log->data ?? ''), 100) }}
                            </td>
                        </tr>
                        <tr class="collapse" id="log-detail-{{ $log->id }}">
                            <td colspan="5" style="padding: 0; background: rgba(255,255,255,0.02);">
                                <div class="p-3">
                                    <strong style="color: var(--text-primary); font-size: 0.8rem;">Full Data</strong>
                                    <pre style="color: var(--text-secondary); font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px; margin-top: 8px; max-height: 300px; overflow-y: auto; white-space: pre-wrap;">{{ is_array($log->data) ? json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ($log->data ?? 'No data') }}</pre>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-3">
                {{ $logs->links() }}
            </div>
        @else
            <div class="ptx-empty-state">
                <i class="bi bi-journal-text d-block"></i>
                <p>No log entries found matching your filters.</p>
            </div>
        @endif
    </div>
</div>
@endsection
