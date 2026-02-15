@extends('layouts.admin')

@section('title', 'Trade #' . $trade->id)

@section('content')
<div class="mb-3">
    <a href="{{ route('trades.index') }}" style="color: var(--accent); text-decoration: none;">
        <i class="bi bi-arrow-left me-1"></i> Back to Trades
    </a>
</div>

{{-- Trade Info Card --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
            Trade #{{ $trade->id }}
        </h5>
        <div>
            @switch($trade->status)
                @case('won')
                    <span class="ptx-badge ptx-badge-success" style="font-size: 0.9rem; padding: 0.35em 0.75em;">Won</span>
                    @break
                @case('lost')
                    <span class="ptx-badge ptx-badge-danger" style="font-size: 0.9rem; padding: 0.35em 0.75em;">Lost</span>
                    @break
                @case('open')
                    <span class="ptx-badge ptx-badge-info" style="font-size: 0.9rem; padding: 0.35em 0.75em;">Open</span>
                    @break
                @case('cancelled')
                    <span class="ptx-badge ptx-badge-warning" style="font-size: 0.9rem; padding: 0.35em 0.75em;">Cancelled</span>
                    @break
                @case('pending')
                    <span class="ptx-badge ptx-badge-secondary" style="font-size: 0.9rem; padding: 0.35em 0.75em;">Pending</span>
                    @break
                @default
                    <span class="ptx-badge ptx-badge-secondary" style="font-size: 0.9rem; padding: 0.35em 0.75em;">{{ ucfirst($trade->status) }}</span>
            @endswitch
        </div>
    </div>
    <div class="ptx-card-body">
        {{-- Market Question --}}
        <div class="mb-3">
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Market Question</div>
            <div style="font-size: 1.05rem;">{{ $trade->market_question ?? '-' }}</div>
        </div>

        @if($trade->market_slug)
            <div class="mb-3">
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Slug</div>
                <div style="font-size: 0.9rem; color: var(--text-secondary);">{{ $trade->market_slug }}</div>
            </div>
        @endif

        @if($trade->condition_id)
            <div class="mb-3">
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Condition ID</div>
                <div style="font-size: 0.85rem; font-family: monospace; color: var(--text-secondary);">{{ $trade->condition_id }}</div>
            </div>
        @endif

        <div class="row g-3">
            {{-- Left Column --}}
            <div class="col-md-6">
                <table class="w-100" style="font-size: 0.9rem;">
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary); width: 45%;">Asset</td>
                        <td class="py-2"><strong>{{ $trade->asset }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Side</td>
                        <td class="py-2">
                            @if($trade->side === 'YES')
                                <span class="ptx-badge ptx-badge-success">YES</span>
                            @else
                                <span class="ptx-badge ptx-badge-danger">NO</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Entry Price</td>
                        <td class="py-2">{{ $trade->entry_price !== null ? number_format((float) $trade->entry_price, 4) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Amount</td>
                        <td class="py-2">${{ number_format((float) $trade->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Entry Time</td>
                        <td class="py-2">{{ $trade->entry_at?->format('M d, Y H:i:s') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Confidence</td>
                        <td class="py-2">
                            @if($trade->confidence_score !== null)
                                <strong>{{ number_format((float) $trade->confidence_score * 100, 2) }}%</strong>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Spot at Entry</td>
                        <td class="py-2">{{ $trade->external_spot_at_entry !== null ? '$' . number_format((float) $trade->external_spot_at_entry, 2) : '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- Right Column --}}
            <div class="col-md-6">
                <table class="w-100" style="font-size: 0.9rem;">
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary); width: 45%;">Exit Price</td>
                        <td class="py-2">{{ $trade->exit_price !== null ? number_format((float) $trade->exit_price, 4) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Potential Payout</td>
                        <td class="py-2">{{ $trade->potential_payout !== null ? '$' . number_format((float) $trade->potential_payout, 2) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">P&amp;L</td>
                        <td class="py-2">
                            @if($trade->pnl !== null)
                                <strong style="color: {{ (float) $trade->pnl >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-size: 1.1rem;">
                                    {{ (float) $trade->pnl >= 0 ? '+' : '' }}${{ number_format((float) $trade->pnl, 2) }}
                                </strong>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Resolved At</td>
                        <td class="py-2">{{ $trade->resolved_at?->format('M d, Y H:i:s') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Decision Tier</td>
                        <td class="py-2">
                            <span class="ptx-badge ptx-badge-secondary">{{ $trade->decision_tier ?? '-' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Market End Time</td>
                        <td class="py-2">{{ $trade->market_end_time?->format('M d, Y H:i:s') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Spot at Resolution</td>
                        <td class="py-2">
                            @if($trade->external_spot_at_resolution !== null)
                                ${{ number_format((float) $trade->external_spot_at_resolution, 2) }}
                                @if($trade->external_spot_at_entry !== null && (float) $trade->external_spot_at_entry > 0)
                                    @php
                                        $change = (((float) $trade->external_spot_at_resolution - (float) $trade->external_spot_at_entry) / (float) $trade->external_spot_at_entry) * 100;
                                    @endphp
                                    <span style="color: {{ $change >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-size: 0.8rem;">
                                        ({{ $change >= 0 ? '+' : '' }}{{ number_format($change, 2) }}%)
                                    </span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Decision Reasoning --}}
        @if(!empty($trade->decision_reasoning))
            <div class="mt-3">
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;" class="mb-1">Decision Reasoning</div>
                <ul class="mb-0" style="padding-left: 1.2rem; color: var(--text-secondary); font-size: 0.9rem;">
                    @foreach($trade->decision_reasoning as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Audit Link --}}
        @if($trade->status === 'lost')
            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.06);">
                @if($trade->audited && $audit)
                    <a href="{{ route('audits.show', $audit) }}" class="btn-ptx-primary btn-ptx-sm">
                        <i class="bi bi-robot me-1"></i> View Audit Report
                    </a>
                @elseif(!$trade->audited)
                    <span class="ptx-badge ptx-badge-warning">
                        <i class="bi bi-hourglass-split me-1"></i> Audit pending
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Timeline --}}
<div class="ptx-card mb-4">
    <div class="ptx-card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> Timeline</h5>
    </div>
    <div class="ptx-card-body">
        @if($trade->tradeLogs->isEmpty())
            <p style="color: var(--text-secondary); margin: 0;">No log entries recorded for this trade.</p>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach($trade->tradeLogs as $log)
                    <div style="border-left: 2px solid rgba(255,255,255,0.1); padding-left: 1rem;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            @switch($log->event)
                                @case('placed')
                                    <span class="ptx-badge ptx-badge-success" style="font-size: 0.7rem;">PLACED</span>
                                    @break
                                @case('filled')
                                    <span class="ptx-badge ptx-badge-info" style="font-size: 0.7rem;">FILLED</span>
                                    @break
                                @case('resolved')
                                    <span style="font-size: 0.7rem; display: inline-block; padding: 0.15em 0.5em; border-radius: 4px; background: rgba(156, 39, 176, 0.15); color: #ce93d8;">RESOLVED</span>
                                    @break
                                @case('error')
                                    <span class="ptx-badge ptx-badge-danger" style="font-size: 0.7rem;">ERROR</span>
                                    @break
                                @default
                                    <span class="ptx-badge ptx-badge-secondary" style="font-size: 0.7rem;">{{ strtoupper($log->event) }}</span>
                            @endswitch
                            <span style="color: var(--text-secondary); font-size: 0.8rem;">{{ $log->created_at->format('M d, H:i:s') }}</span>
                        </div>
                        @if(!empty($log->data))
                            <details>
                                <summary style="color: var(--text-secondary); font-size: 0.8rem; cursor: pointer; user-select: none;">Show data</summary>
                                <pre style="background: rgba(0,0,0,0.25); border-radius: 6px; padding: 0.75rem; margin: 0.5rem 0 0 0; font-size: 0.78rem; color: var(--text-secondary); overflow-x: auto; max-height: 200px;">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- AI Decisions --}}
@if($trade->aiDecisions->isNotEmpty())
    <div class="ptx-card mb-4">
        <div class="ptx-card-header">
            <h5 class="mb-0"><i class="bi bi-cpu me-1"></i> AI Decisions</h5>
        </div>
        <div class="ptx-card-body p-0">
            @foreach($trade->aiDecisions as $index => $decision)
                <details class="p-3" style="{{ !$loop->last ? 'border-bottom: 1px solid rgba(255,255,255,0.06);' : '' }}">
                    <summary style="cursor: pointer; user-select: none;">
                        <span class="ptx-badge ptx-badge-secondary me-1" style="font-size: 0.7rem;">{{ strtoupper($decision->tier) }}</span>
                        <span style="font-size: 0.85rem;">{{ $decision->decision_type ?? 'Decision' }}</span>
                        <span style="color: var(--text-secondary); font-size: 0.8rem; margin-left: 0.5rem;">
                            {{ $decision->model_used }} &middot;
                            {{ number_format($decision->tokens_input + $decision->tokens_output) }} tokens &middot;
                            ${{ number_format((float) $decision->cost_usd, 4) }}
                        </span>
                        <span style="color: var(--text-secondary); font-size: 0.75rem; margin-left: 0.5rem;">
                            {{ $decision->created_at->format('M d, H:i:s') }}
                        </span>
                    </summary>
                    <div class="mt-3">
                        @if($decision->prompt)
                            <div class="mb-2">
                                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;" class="mb-1">Prompt</div>
                                <pre style="background: rgba(0,0,0,0.25); border-radius: 6px; padding: 0.75rem; font-size: 0.78rem; color: var(--text-secondary); overflow-x: auto; max-height: 300px; white-space: pre-wrap;">{{ $decision->prompt }}</pre>
                            </div>
                        @endif
                        @if($decision->response)
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;" class="mb-1">Response</div>
                                <pre style="background: rgba(0,0,0,0.25); border-radius: 6px; padding: 0.75rem; font-size: 0.78rem; color: var(--text-secondary); overflow-x: auto; max-height: 300px; white-space: pre-wrap;">{{ $decision->response }}</pre>
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </div>
@endif
@endsection
