@extends('layouts.admin')

@section('title', 'Audit #' . $audit->id)

@section('content')
<div class="mb-3">
    <a href="{{ route('audits.index') }}" style="color: var(--accent); font-size: 0.9rem;"><i class="bi bi-arrow-left"></i> Back to Audits</a>
</div>

@if(session('success'))
    <div class="ptx-alert ptx-alert-success mb-3">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="ptx-alert ptx-alert-danger mb-3">
        <i class="bi bi-x-circle-fill"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- Audit Info --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>Audit #{{ $audit->id }}</h5>
        <div class="d-flex gap-2 align-items-center">
            @php
                $triggerBadge = match($audit->trigger) {
                    'post_loss' => 'danger',
                    'daily_review' => 'info',
                    'weekly_review' => 'primary',
                    'manual' => 'warning',
                    default => 'secondary',
                };
                $statusBadge = match($audit->status) {
                    'pending_review' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'secondary',
                    'auto_applied' => 'info',
                    default => 'secondary',
                };
            @endphp
            <span class="ptx-badge ptx-badge-{{ $triggerBadge }}">{{ str_replace('_', ' ', $audit->trigger) }}</span>
            <span class="ptx-badge ptx-badge-{{ $statusBadge }}">{{ str_replace('_', ' ', $audit->status) }}</span>
        </div>
    </div>
    <div class="ptx-card-body">
        <table class="w-100" style="font-size: 0.9rem;">
            <tr>
                <td style="color: var(--text-secondary); padding: 6px 0; width: 150px;">Created</td>
                <td>{{ $audit->created_at?->format('M d, Y H:i:s') }}</td>
            </tr>
            <tr>
                <td style="color: var(--text-secondary); padding: 6px 0;">Reviewed</td>
                <td>{{ $audit->reviewed_at?->format('M d, Y H:i:s') ?? '—' }}</td>
            </tr>
            <tr>
                <td style="color: var(--text-secondary); padding: 6px 0;">Applied</td>
                <td>{{ $audit->applied_at?->format('M d, Y H:i:s') ?? '—' }}</td>
            </tr>
            <tr>
                <td style="color: var(--text-secondary); padding: 6px 0;">Trades Analyzed</td>
                <td>{{ count($audit->losing_trade_ids ?? []) }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- Analysis --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>Analysis</h5>
    </div>
    <div class="ptx-card-body">
        <div style="color: var(--text-secondary); font-size: 0.9rem; white-space: pre-line; line-height: 1.6;">{{ $audit->analysis }}</div>
    </div>
</div>

{{-- Losing Trades Referenced --}}
@if($losingTrades->count() > 0)
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>Losing Trades Referenced</h5>
    </div>
    <div class="ptx-card-body p-0">
        <table class="ptx-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Asset</th>
                    <th>Side</th>
                    <th>Amount</th>
                    <th>P&L</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($losingTrades as $trade)
                    <tr>
                        <td>#{{ $trade->id }}</td>
                        <td>{{ $trade->asset }}</td>
                        <td>
                            <span class="ptx-badge {{ $trade->side === 'YES' ? 'ptx-badge-success' : 'ptx-badge-danger' }}">{{ $trade->side }}</span>
                        </td>
                        <td>${{ number_format((float)$trade->amount, 2) }}</td>
                        <td style="color: var(--loss); font-weight: 600;">
                            {{ $trade->pnl !== null ? '-$' . number_format(abs((float)$trade->pnl), 2) : '—' }}
                        </td>
                        <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $trade->created_at?->format('M d, H:i') }}</td>
                        <td>
                            <a href="{{ route('trades.show', $trade) }}" class="btn-ptx-secondary btn-ptx-sm">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Suggested Fixes --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5>Suggested Fixes</h5>
    </div>
    <div class="ptx-card-body p-0">
        @if(!empty($audit->suggested_fixes))
            <table class="ptx-table">
                <thead>
                    <tr>
                        <th>#</th>
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
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $fix['param'] ?? 'N/A' }}</code></td>
                            <td>{{ $fix['current'] ?? '—' }}</td>
                            <td style="color: var(--accent); font-weight: 600;">{{ $fix['suggested'] ?? '—' }}</td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ $fix['rationale'] ?? '' }}</td>
                            <td>
                                @if(isset($fix['confidence']))
                                    <span style="color: {{ (float)$fix['confidence'] >= 0.8 ? 'var(--profit)' : ((float)$fix['confidence'] >= 0.5 ? 'var(--accent)' : 'var(--loss)') }};">
                                        {{ number_format((float)$fix['confidence'] * 100, 0) }}%
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @php $fixStatus = $fix['status'] ?? 'pending_review'; @endphp
                                @if($fixStatus === 'pending_review')
                                    <div class="d-flex gap-1">
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
                                    @if(!empty($fix['reject_reason']))
                                        <div style="color: var(--text-secondary); font-size: 0.75rem; margin-top: 2px;">{{ $fix['reject_reason'] }}</div>
                                    @endif
                                @elseif($fixStatus === 'auto_applied')
                                    <span class="ptx-badge ptx-badge-info">Auto Applied</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ptx-empty-state">
                <i class="bi bi-wrench d-block"></i>
                <p>No suggested fixes for this audit.</p>
            </div>
        @endif
    </div>
</div>

{{-- Review Notes --}}
@if($audit->review_notes)
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Review Notes</h5>
    </div>
    <div class="ptx-card-body">
        <p style="color: var(--text-secondary); font-size: 0.9rem; white-space: pre-line;">{{ $audit->review_notes }}</p>
    </div>
</div>
@endif
@endsection
