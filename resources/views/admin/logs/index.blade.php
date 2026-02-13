@extends('layouts.super-admin')

@section('title', 'Trade Logs')

@section('content')
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    @if(isset($users))
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->account_id }})
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Event Type</label>
                <select name="event" class="form-select">
                    <option value="">All Events</option>
                    @foreach(['order_placed','order_filled','order_cancelled','position_opened','position_closed','signal_generated','risk_check','price_feed','api_error','ai_decision'] as $e)
                        <option value="{{ $e }}" {{ request('event') === $e ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($e)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Level</label>
                <select name="level" class="form-select">
                    <option value="">All Levels</option>
                    @foreach(['info','warning','error','debug'] as $l)
                        <option value="{{ $l }}" {{ request('level') === $l ? 'selected' : '' }}>{{ ucfirst($l) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
            @if(request()->hasAny(['user_id', 'event', 'level']))
                <div class="col-md-1">
                    <a href="/admin/logs" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30px;"></th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Trade ID</th>
                        <th>Event</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr data-bs-toggle="collapse" data-bs-target="#log-data-{{ $log->id }}" role="button" class="cursor-pointer">
                        <td class="ps-3">
                            <i class="bi bi-chevron-right small text-muted"></i>
                        </td>
                        <td class="small text-muted text-nowrap">{{ $log->created_at->format('M j H:i:s') }}</td>
                        <td>
                            @if($log->trade && $log->trade->user)
                                <code class="small">{{ $log->trade->user->account_id }}</code>
                            @else
                                <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($log->trade_id)
                                <a href="/admin/logs?trade_id={{ $log->trade_id }}" class="text-decoration-none small">
                                    #{{ $log->trade_id }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $eventColors = [
                                    'order_placed' => 'primary',
                                    'order_filled' => 'success',
                                    'order_cancelled' => 'secondary',
                                    'position_opened' => 'info',
                                    'position_closed' => 'dark',
                                    'signal_generated' => 'primary',
                                    'risk_check' => 'warning',
                                    'price_feed' => 'secondary',
                                    'api_error' => 'danger',
                                    'ai_decision' => 'info',
                                    'error' => 'danger',
                                    'warning' => 'warning',
                                ];
                                $badgeColor = $eventColors[$log->event] ?? ($log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'secondary'));
                            @endphp
                            <span class="badge bg-{{ $badgeColor }}">{{ $log->event ?? $log->level ?? '—' }}</span>
                        </td>
                        <td class="small text-muted">
                            {{ \Illuminate\Support\Str::limit($log->message ?? json_encode($log->data), 80) }}
                        </td>
                    </tr>
                    <tr class="collapse" id="log-data-{{ $log->id }}">
                        <td colspan="6" class="bg-light p-3">
                            @if($log->message)
                                <div class="mb-2">
                                    <strong class="small">Message:</strong>
                                    <div class="small">{{ $log->message }}</div>
                                </div>
                            @endif
                            @if($log->data)
                                <strong class="small d-block mb-1">Data:</strong>
                                <pre class="bg-white border rounded p-3 mb-0 small" style="max-height: 300px; overflow: auto;">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @endif
                            @if(!$log->message && !$log->data)
                                <span class="text-muted small">No additional data.</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-journal-text fs-3 d-block mb-2"></i>
                            No trade logs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection

@section('extra-styles')
<style>
    [data-bs-toggle="collapse"] .bi-chevron-right {
        transition: transform 0.2s;
    }
    [data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-right {
        transform: rotate(90deg);
    }
    .cursor-pointer { cursor: pointer; }
</style>
@endsection
