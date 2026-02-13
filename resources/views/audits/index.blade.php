@extends('layouts.admin')

@section('title', 'AI Audits')

@section('content')
{{-- Filter Bar --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-body">
        <form method="GET" action="{{ route('audits.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Status</label>
                <select name="status" class="ptx-input ptx-input-sm">
                    <option value="">All Statuses</option>
                    <option value="pending_review" @selected(request('status') === 'pending_review')>Pending Review</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    <option value="auto_applied" @selected(request('status') === 'auto_applied')>Auto Applied</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: var(--text-secondary); font-size: 0.8rem;">Trigger</label>
                <select name="trigger" class="ptx-input ptx-input-sm">
                    <option value="">All Triggers</option>
                    <option value="post_loss" @selected(request('trigger') === 'post_loss')>Post Loss</option>
                    <option value="daily_review" @selected(request('trigger') === 'daily_review')>Daily Review</option>
                    <option value="weekly_review" @selected(request('trigger') === 'weekly_review')>Weekly Review</option>
                    <option value="manual" @selected(request('trigger') === 'manual')>Manual</option>
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
            <div class="col-md-2">
                <button type="submit" class="btn-ptx-primary btn-ptx-sm w-100">Filter</button>
            </div>
            @if(request()->hasAny(['status', 'trigger', 'from', 'to']))
                <div class="col-md-2">
                    <a href="{{ route('audits.index') }}" class="btn-ptx-secondary btn-ptx-sm w-100 d-inline-block text-center">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Audits Table --}}
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>AI Audit Reports</h5>
        <span style="color: var(--text-secondary); font-size: 0.85rem;">{{ $audits->total() }} total</span>
    </div>
    <div class="ptx-card-body p-0">
        @if($audits->count() > 0)
            <div class="accordion" id="auditsAccordion">
                <table class="ptx-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;"></th>
                            <th>Date</th>
                            <th>Trigger</th>
                            <th>Trades</th>
                            <th>Root Cause</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audits as $audit)
                            <tr data-bs-toggle="collapse" data-bs-target="#audit-detail-{{ $audit->id }}" style="cursor: pointer;">
                                <td><i class="bi bi-chevron-down" style="font-size: 0.7rem; color: var(--text-secondary);"></i></td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $audit->created_at?->format('M d, Y H:i') }}</td>
                                <td>
                                    @php
                                        $triggerBadge = match($audit->trigger) {
                                            'post_loss' => 'danger',
                                            'daily_review' => 'info',
                                            'weekly_review' => 'primary',
                                            'manual' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="ptx-badge ptx-badge-{{ $triggerBadge }}">{{ str_replace('_', ' ', $audit->trigger) }}</span>
                                </td>
                                <td>{{ count($audit->losing_trade_ids ?? []) }}</td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ Str::limit($audit->analysis ?? '', 80) }}</td>
                                <td>
                                    @php
                                        $statusBadge = match($audit->status) {
                                            'pending_review' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'secondary',
                                            'auto_applied' => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="ptx-badge ptx-badge-{{ $statusBadge }}">{{ str_replace('_', ' ', $audit->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('audits.show', $audit) }}" class="btn-ptx-secondary btn-ptx-sm" onclick="event.stopPropagation();">View</a>
                                </td>
                            </tr>
                            <tr class="collapse" id="audit-detail-{{ $audit->id }}">
                                <td colspan="7" style="padding: 0; background: rgba(255,255,255,0.02);">
                                    <div class="p-3">
                                        {{-- Analysis --}}
                                        <div class="mb-3">
                                            <strong style="color: var(--text-primary); font-size: 0.85rem;">Analysis</strong>
                                            <p style="color: var(--text-secondary); font-size: 0.85rem; white-space: pre-line; margin-top: 0.5rem;">{{ $audit->analysis }}</p>
                                        </div>

                                        {{-- Suggested Fixes --}}
                                        @if(!empty($audit->suggested_fixes))
                                            <strong style="color: var(--text-primary); font-size: 0.85rem;">Suggested Fixes</strong>
                                            <table class="ptx-table mt-2" style="font-size: 0.85rem;">
                                                <thead>
                                                    <tr>
                                                        <th>Parameter</th>
                                                        <th>Current</th>
                                                        <th>Suggested</th>
                                                        <th>Rationale</th>
                                                        <th>Confidence</th>
                                                        <th>Status / Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($audit->suggested_fixes as $index => $fix)
                                                        <tr>
                                                            <td><code>{{ $fix['param'] ?? 'N/A' }}</code></td>
                                                            <td>{{ $fix['current'] ?? '—' }}</td>
                                                            <td style="color: var(--accent);">{{ $fix['suggested'] ?? '—' }}</td>
                                                            <td style="color: var(--text-secondary);">{{ $fix['rationale'] ?? '' }}</td>
                                                            <td>{{ isset($fix['confidence']) ? number_format((float)$fix['confidence'] * 100, 0) . '%' : '—' }}</td>
                                                            <td>
                                                                @php $fixStatus = $fix['status'] ?? 'pending_review'; @endphp
                                                                @if($fixStatus === 'pending_review')
                                                                    <div class="d-flex gap-1" onclick="event.stopPropagation();">
                                                                        <form method="POST" action="{{ route('audits.approve-fix', $audit) }}" class="d-inline">
                                                                            @csrf
                                                                            <input type="hidden" name="fix_index" value="{{ $index }}">
                                                                            <button type="submit" class="btn-ptx-primary btn-ptx-sm">Approve</button>
                                                                        </form>
                                                                        <form method="POST" action="{{ route('audits.reject-fix', $audit) }}" class="d-inline">
                                                                            @csrf
                                                                            <input type="hidden" name="fix_index" value="{{ $index }}">
                                                                            <button type="submit" class="btn-ptx-danger btn-ptx-sm">Reject</button>
                                                                        </form>
                                                                    </div>
                                                                @elseif($fixStatus === 'approved')
                                                                    <span class="ptx-badge ptx-badge-success">Approved</span>
                                                                @elseif($fixStatus === 'rejected')
                                                                    <span class="ptx-badge ptx-badge-secondary">Rejected</span>
                                                                @elseif($fixStatus === 'auto_applied')
                                                                    <span class="ptx-badge ptx-badge-info">Auto Applied</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $audits->links() }}
            </div>
        @else
            <div class="ptx-empty-state">
                <i class="bi bi-robot d-block"></i>
                <p>No audit reports found matching your filters.</p>
            </div>
        @endif
    </div>
</div>
@endsection
