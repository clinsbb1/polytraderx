@extends('layouts.admin')

@section('title', 'Market Scans')

@section('content')
<div class="ptx-alert ptx-alert-info mb-4" role="alert">
    <i class="bi bi-info-circle-fill"></i>
    <span>Market scan history is kept for <strong>24 hours</strong>. Older entries are automatically removed.</span>
</div>

<div class="ptx-card mb-4">
    <div class="ptx-card-body">
        <h6 class="mb-3">How to read Market Scans</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-secondary">
                    <strong>Event</strong>: What happened in the simulation cycle (scan, skip, no match, market check).
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">
                    <strong>Market</strong>: Asset and market ID that was evaluated (if applicable).
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">
                    <strong>Match</strong>: Whether that market matched your strategy conditions.
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">
                    <strong>Action</strong>: Engine decision for that check (skip, trade placed, etc.).
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">
                    <strong>Message</strong>: Human summary of what happened in that row.
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">
                    <strong>Context</strong>: Raw details including scanned totals, 5min/15min split, entry window, and nearest close.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ptx-card mb-4">
    <div class="ptx-card-body">
        <form method="GET" action="{{ route('logs.market-scans') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Asset, market ID, action..." class="ptx-input ptx-input-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Event</label>
                <select name="event" class="ptx-input ptx-input-sm">
                    <option value="">All Events</option>
                    <option value="cycle_scanned" @selected(request('event') === 'cycle_scanned')>Cycle Scanned</option>
                    <option value="cycle_skipped" @selected(request('event') === 'cycle_skipped')>Cycle Skipped</option>
                    <option value="no_match" @selected(request('event') === 'no_match')>No Match</option>
                    <option value="market_checked" @selected(request('event') === 'market_checked')>Market Checked</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Match</label>
                <select name="matched" class="ptx-input ptx-input-sm">
                    <option value="">All</option>
                    <option value="yes" @selected(request('matched') === 'yes')>Matched</option>
                    <option value="no" @selected(request('matched') === 'no')>Not Matched</option>
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
        </form>
    </div>
</div>

<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Market Scan Timeline</h5>
        <span style="color: var(--text-secondary); font-size: 0.85rem;">{{ $activities->total() }} entries</span>
    </div>
    <div class="ptx-card-body p-0">
        @if($activities->count() > 0)
            <table class="ptx-table">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Time</th>
                        <th>Event</th>
                        <th>Market</th>
                        <th>Match</th>
                        <th>Action</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $item)
                        <tr data-bs-toggle="collapse" data-bs-target="#activity-detail-{{ $item->id }}" style="cursor:pointer;">
                            <td><i class="bi bi-chevron-down" style="font-size: 0.7rem; color: var(--text-secondary);"></i></td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $item->created_at?->format('M d, H:i:s') }}</td>
                            <td><span class="ptx-badge ptx-badge-secondary">{{ $item->event }}</span></td>
                            <td style="font-size:0.85rem;">
                                @if($item->asset)
                                    {{ $item->asset }}
                                    @if($item->market_id)
                                        <div style="color: var(--text-secondary)">#{{ \Illuminate\Support\Str::limit($item->market_id, 14, '') }}</div>
                                    @endif
                                @else
                                    <span style="color: var(--text-secondary)">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->matched_strategy === true)
                                    <span class="ptx-badge ptx-badge-success">Yes</span>
                                @elseif($item->matched_strategy === false)
                                    <span class="ptx-badge ptx-badge-danger">No</span>
                                @else
                                    <span style="color: var(--text-secondary)">—</span>
                                @endif
                            </td>
                            <td style="font-size:0.85rem;">{{ $item->action ?? '—' }}</td>
                            <td style="color: var(--text-secondary); font-size:0.85rem;">{{ \Illuminate\Support\Str::limit($item->message ?? '', 80) }}</td>
                        </tr>
                        <tr class="collapse" id="activity-detail-{{ $item->id }}">
                            <td colspan="7" style="padding:0;background:rgba(255,255,255,0.02);">
                                <div class="p-3">
                                    <strong style="color: var(--text-primary); font-size: 0.8rem;">Context</strong>
                                    <pre style="color: var(--text-secondary); font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px; margin-top: 8px; max-height: 260px; overflow-y: auto; white-space: pre-wrap;">{{ is_array($item->context) ? json_encode($item->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'No context' }}</pre>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-3 d-flex justify-content-center">
                {{ $activities->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="ptx-empty-state">
                <i class="bi bi-cpu d-block"></i>
                <p>No market scans yet. Activity appears once simulator cycles run.</p>
            </div>
        @endif
    </div>
</div>
@endsection
