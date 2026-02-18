@extends('layouts.admin')

@section('title', 'Trade #' . $trade->id)

@section('content')
<div class="mb-3">
    <a href="{{ route('trades.index') }}" style="color: var(--accent); text-decoration: none;">
        <i class="bi bi-arrow-left me-1"></i> Back to Trades
    </a>
</div>

@php
    $status = strtolower((string) ($trade->status ?? 'unknown'));
    $side = strtoupper((string) ($trade->side ?? '-'));

    $formatDate = static function (mixed $value): string {
        if ($value === null || $value === '') {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('M d, Y H:i:s');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $formatNumber = static function (mixed $value, int $decimals = 2): string {
        if (!is_numeric($value)) {
            return '-';
        }

        return number_format((float) $value, $decimals);
    };

    $reasoning = [];
    if (isset($trade->decision_reasoning) && is_string($trade->decision_reasoning)) {
        $decoded = json_decode($trade->decision_reasoning, true);
        if (is_array($decoded)) {
            $reasoning = $decoded;
        }
    }
@endphp

<div class="ptx-card mb-4">
    <div class="ptx-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
            Trade #{{ $trade->id }}
        </h5>
        <div>
            @switch($status)
                @case('won')
                    <span class="ptx-badge ptx-badge-success">Won</span>
                    @break
                @case('lost')
                    <span class="ptx-badge ptx-badge-danger">Lost</span>
                    @break
                @case('open')
                    <span class="ptx-badge ptx-badge-info">Open</span>
                    @break
                @case('cancelled')
                    <span class="ptx-badge ptx-badge-warning">Cancelled</span>
                    @break
                @case('pending')
                    <span class="ptx-badge ptx-badge-secondary">Pending</span>
                    @break
                @default
                    <span class="ptx-badge ptx-badge-secondary">{{ strtoupper((string) ($trade->status ?? 'UNKNOWN')) }}</span>
            @endswitch
        </div>
    </div>
    <div class="ptx-card-body">
        <div class="mb-3">
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Market Question</div>
            <div style="font-size: 1.05rem;">{{ (string) ($trade->market_question ?? '-') }}</div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <table class="w-100" style="font-size: 0.9rem;">
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary); width: 45%;">Asset</td>
                        <td class="py-2"><strong>{{ (string) ($trade->asset ?? '-') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Side</td>
                        <td class="py-2">
                            @if($side === 'YES')
                                <span class="ptx-badge ptx-badge-success">YES</span>
                            @elseif($side === 'NO')
                                <span class="ptx-badge ptx-badge-danger">NO</span>
                            @else
                                <span class="ptx-badge ptx-badge-secondary">{{ $side }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Entry Price</td>
                        <td class="py-2">{{ $formatNumber($trade->entry_price ?? null, 4) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Amount</td>
                        <td class="py-2">${{ $formatNumber($trade->amount ?? null, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Entry Time</td>
                        <td class="py-2">{{ $formatDate($trade->entry_at ?? null) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Confidence</td>
                        <td class="py-2">
                            @if(is_numeric($trade->confidence_score ?? null))
                                <strong>{{ number_format((float) $trade->confidence_score * 100, 2) }}%</strong>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Spot at Entry</td>
                        <td class="py-2">{{ is_numeric($trade->external_spot_at_entry ?? null) ? '$' . $formatNumber($trade->external_spot_at_entry, 2) : '-' }}</td>
                    </tr>
                </table>
            </div>

            <div class="col-md-6">
                <table class="w-100" style="font-size: 0.9rem;">
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary); width: 45%;">Exit Price</td>
                        <td class="py-2">{{ $formatNumber($trade->exit_price ?? null, 4) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Potential Payout</td>
                        <td class="py-2">{{ is_numeric($trade->potential_payout ?? null) ? '$' . $formatNumber($trade->potential_payout, 2) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">P&amp;L</td>
                        <td class="py-2">
                            @if(is_numeric($trade->pnl ?? null))
                                <strong style="color: {{ (float) $trade->pnl >= 0 ? 'var(--profit)' : 'var(--loss)' }}; font-size: 1.1rem;">
                                    {{ (float) $trade->pnl >= 0 ? '+' : '' }}${{ $formatNumber($trade->pnl, 2) }}
                                </strong>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Resolved At</td>
                        <td class="py-2">{{ $formatDate($trade->resolved_at ?? null) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Decision Tier</td>
                        <td class="py-2">
                            <span class="ptx-badge ptx-badge-secondary">{{ strtoupper((string) ($trade->decision_tier ?? '-')) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Market End Time</td>
                        <td class="py-2">{{ $formatDate($trade->market_end_time ?? null) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2" style="color: var(--text-secondary);">Spot at Resolution</td>
                        <td class="py-2">{{ is_numeric($trade->external_spot_at_resolution ?? null) ? '$' . $formatNumber($trade->external_spot_at_resolution, 2) : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if(!empty($trade->market_slug))
            <div class="mt-3">
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Market Slug</div>
                <div style="font-size: 0.9rem; color: var(--text-secondary);">{{ (string) $trade->market_slug }}</div>
            </div>
        @endif

        @if(!empty($trade->market_id))
            <div class="mt-3">
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Market ID</div>
                <div style="font-size: 0.85rem; font-family: monospace; color: var(--text-secondary);">{{ (string) $trade->market_id }}</div>
            </div>
        @endif

        @if(!empty($reasoning))
            <div class="mt-3">
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;" class="mb-1">Decision Notes</div>
                <ul class="mb-0" style="padding-left: 1.2rem; color: var(--text-secondary); font-size: 0.9rem;">
                    @foreach($reasoning as $reason)
                        <li>{{ (string) $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.06);">
            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                Created: {{ $formatDate($trade->created_at ?? null) }}
                <span class="mx-2">|</span>
                Updated: {{ $formatDate($trade->updated_at ?? null) }}
            </div>
        </div>
    </div>
</div>
@endsection
