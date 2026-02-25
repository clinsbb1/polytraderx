@extends('layouts.admin')

@section('title', 'Trades')

@section('content')
<div class="ptx-card mb-4">
    <div class="ptx-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">All Trades</h5>
        <a href="{{ route('trades.export', request()->query()) }}" class="btn-ptx-primary btn-ptx-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
    <div class="ptx-card-body">
        <form method="GET" action="{{ route('trades.index') }}" class="row g-2 align-items-end mb-0">
            <div class="col-6 col-md-auto">
                <label class="form-label mb-1" style="font-size: 0.75rem; color: var(--text-secondary);">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="ptx-input ptx-input-sm">
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label mb-1" style="font-size: 0.75rem; color: var(--text-secondary);">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="ptx-input ptx-input-sm">
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label mb-1" style="font-size: 0.75rem; color: var(--text-secondary);">Asset</label>
                <select name="asset" class="ptx-input ptx-input-sm">
                    <option value="">All</option>
                    <option value="BTC" {{ request('asset') === 'BTC' ? 'selected' : '' }}>BTC</option>
                    <option value="ETH" {{ request('asset') === 'ETH' ? 'selected' : '' }}>ETH</option>
                    <option value="SOL" {{ request('asset') === 'SOL' ? 'selected' : '' }}>SOL</option>
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label mb-1" style="font-size: 0.75rem; color: var(--text-secondary);">Side</label>
                <select name="side" class="ptx-input ptx-input-sm">
                    <option value="">All</option>
                    <option value="YES" {{ request('side') === 'YES' ? 'selected' : '' }}>YES</option>
                    <option value="NO" {{ request('side') === 'NO' ? 'selected' : '' }}>NO</option>
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label mb-1" style="font-size: 0.75rem; color: var(--text-secondary);">Status</label>
                <select name="status" class="ptx-input ptx-input-sm">
                    <option value="">All</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="won" {{ request('status') === 'won' ? 'selected' : '' }}>Won</option>
                    <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Lost</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn-ptx-primary btn-ptx-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('trades.index') }}" style="color: var(--text-secondary); font-size: 0.85rem; align-self: center;">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="ptx-card">
    <div class="ptx-card-body p-0">
        @if($trades->isEmpty())
            <div class="ptx-empty-state">
                <i class="bi bi-currency-exchange d-block" style="font-size: 2rem; color: var(--text-secondary);"></i>
                <p class="mt-2 mb-0" style="color: var(--text-secondary);">No trades found matching your filters.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="ptx-table mb-0">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Asset</th>
                            <th>Market</th>
                            <th>Side</th>
                            <th>Amount</th>
                            <th>Entry</th>
                            <th>Exit</th>
                            <th>P&amp;L</th>
                            <th>Status</th>
                            <th>Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trades as $trade)
                            <tr style="cursor: pointer;" onclick="window.location='{{ route('trades.show', $trade) }}'">
                                <td style="white-space: nowrap;">{{ $trade->entry_at?->format('M d, H:i') ?? '-' }}</td>
                                <td><strong>{{ $trade->asset }}</strong></td>
                                <td title="{{ $trade->market_question }}">{{ Str::limit($trade->market_question, 40) }}</td>
                                <td>
                                    @if($trade->side === 'YES')
                                        <span class="ptx-badge ptx-badge-success">YES</span>
                                    @else
                                        <span class="ptx-badge ptx-badge-danger">NO</span>
                                    @endif
                                </td>
                                <td>${{ number_format((float) $trade->amount, 2) }}</td>
                                <td>{{ $trade->entry_price !== null ? number_format((float) $trade->entry_price, 4) : '-' }}</td>
                                <td>{{ $trade->exit_price !== null ? number_format((float) $trade->exit_price, 4) : '-' }}</td>
                                <td>
                                    @if($trade->pnl !== null)
                                        <strong style="color: {{ $trade->status === 'won' ? 'var(--profit)' : ($trade->status === 'lost' ? 'var(--loss)' : 'var(--text-secondary)') }};">
                                            {{ $trade->status === 'won' ? '+' : ($trade->status === 'lost' ? '-' : '') }}${{ number_format(abs((float) $trade->pnl), 2) }}
                                        </strong>
                                    @else
                                        <span style="color: var(--text-secondary);">-</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($trade->status)
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
                                            <span class="ptx-badge ptx-badge-secondary">{{ ucfirst($trade->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <span class="ptx-badge ptx-badge-secondary" style="font-size: 0.7rem;">{{ $trade->decision_tier ?? '-' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if($trades->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $trades->links() }}
    </div>
@endif
@endsection
