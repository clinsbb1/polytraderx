@extends('layouts.admin')

@section('title', 'Daily Summaries')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0" style="font-family: var(--font-display);">Daily Summaries</h4>
        <div class="text-muted small mt-1">Complete history of simulated daily performance</div>
    </div>
    <a href="{{ route('balance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Balance & Equity
    </a>
</div>

{{-- Overall Stats Strip --}}
@if($allStats && (int)$allStats->days > 0)
@php
    $totalGross = (float) ($allStats->gross_pnl ?? 0);
    $totalTrades = (int) ($allStats->trades ?? 0);
    $totalWins = (int) ($allStats->wins ?? 0);
    $totalLosses = (int) ($allStats->losses ?? 0);
    $overallWinRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center py-3">
            <div class="stat-label">Trading Days</div>
            <div class="stat-value text-accent" style="font-size: 1.6rem;">{{ (int)$allStats->days }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center py-3">
            <div class="stat-label">Total Trades</div>
            <div class="stat-value text-accent" style="font-size: 1.6rem;">{{ $totalTrades }}</div>
            <div style="color: var(--text-secondary); font-size: 0.72rem;">{{ $totalWins }}W · {{ $totalLosses }}L</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center py-3">
            <div class="stat-label">Win Rate</div>
            <div class="stat-value text-accent" style="font-size: 1.6rem;">{{ $overallWinRate }}%</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ptx-stat-card text-center py-3">
            <div class="stat-label">Total P&L</div>
            <div class="stat-value {{ $totalGross >= 0 ? 'text-profit' : 'text-loss' }}" style="font-size: 1.6rem;">
                {{ $totalGross >= 0 ? '+' : '' }}${{ number_format($totalGross, 2) }}
            </div>
        </div>
    </div>
</div>
@endif

{{-- Table --}}
<div class="ptx-card">
    <div class="ptx-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">History</h5>
        @if($summaries->total() > 0)
            <span class="text-muted small">{{ $summaries->total() }} day{{ $summaries->total() !== 1 ? 's' : '' }} recorded</span>
        @endif
    </div>
    <div class="ptx-card-body p-0">
        @if($summaries->count() > 0)
        <div class="table-responsive">
            <table class="ptx-table mb-0" style="min-width: 680px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Balance</th>
                        <th>P&L</th>
                        <th>Trades</th>
                        <th>Win Rate</th>
                        <th>Cumulative</th>
                        <th>Best / Worst</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaries as $day)
                    @php
                        $gross = (float) $day->gross_pnl;
                        $cumul = (float) ($day->cumulative_pnl ?? 0);
                        $startBal = $day->starting_balance !== null ? (float) $day->starting_balance : null;
                        $endBal   = $day->ending_balance   !== null ? (float) $day->ending_balance   : null;
                    @endphp
                    <tr>
                        {{-- Date --}}
                        <td style="white-space: nowrap;">
                            <span style="font-weight: 600; color: var(--text-primary);">
                                {{ $day->date->format('D') }}
                            </span>
                            <span style="color: var(--text-secondary); font-size: 0.82rem;">
                                {{ $day->date->format('M j, Y') }}
                            </span>
                        </td>

                        {{-- Balance start → end --}}
                        <td style="white-space: nowrap;">
                            @if($startBal !== null && $endBal !== null)
                                <span style="color: var(--text-secondary); font-size: 0.82rem;">${{ number_format($startBal, 2) }}</span>
                                <span style="color: var(--text-secondary); margin: 0 3px;">→</span>
                                <span style="font-weight: 600; color: {{ $endBal >= $startBal ? 'var(--profit)' : 'var(--loss)' }};">${{ number_format($endBal, 2) }}</span>
                            @else
                                <span style="color: var(--text-secondary);">—</span>
                            @endif
                        </td>

                        {{-- P&L --}}
                        <td style="font-weight: 600; white-space: nowrap; color: {{ $gross >= 0 ? 'var(--profit)' : 'var(--loss)' }};">
                            {{ $gross >= 0 ? '+' : '' }}${{ number_format($gross, 2) }}
                        </td>

                        {{-- Trades --}}
                        <td>
                            <span style="color: var(--text-primary);">{{ $day->total_trades }}</span>
                            <span style="color: var(--text-secondary); font-size: 0.8rem;">
                                (<span style="color: var(--profit);">{{ $day->wins }}W</span>
                                / <span style="color: var(--loss);">{{ $day->losses }}L</span>)
                            </span>
                        </td>

                        {{-- Win Rate --}}
                        <td>
                            @php $wr = (float) $day->win_rate; @endphp
                            <span style="color: {{ $wr >= 60 ? 'var(--profit)' : ($wr >= 40 ? 'var(--text-primary)' : 'var(--loss)') }}; font-weight: 600;">
                                {{ number_format($wr, 1) }}%
                            </span>
                        </td>

                        {{-- Cumulative P&L --}}
                        <td style="font-weight: 600; white-space: nowrap; color: {{ $cumul >= 0 ? 'var(--profit)' : 'var(--loss)' }};">
                            {{ $cumul >= 0 ? '+' : '' }}${{ number_format($cumul, 2) }}
                        </td>

                        {{-- Best / Worst --}}
                        <td style="font-size: 0.8rem; white-space: nowrap;">
                            @if($day->best_trade_id && $day->bestTrade)
                                <span style="color: var(--profit);">
                                    ▲ {{ $day->bestTrade->asset }} {{ $day->bestTrade->side }}
                                    +${{ number_format(abs((float)$day->bestTrade->pnl), 2) }}
                                </span>
                            @endif
                            @if($day->worst_trade_id && $day->worstTrade)
                                <br>
                                <span style="color: var(--loss);">
                                    ▼ {{ $day->worstTrade->asset }} {{ $day->worstTrade->side }}
                                    -${{ number_format(abs((float)$day->worstTrade->pnl), 2) }}
                                </span>
                            @endif
                            @if(!$day->best_trade_id && !$day->worst_trade_id)
                                <span style="opacity: 0.4;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($summaries->hasPages())
        <div class="px-4 py-3" style="border-top: 1px solid var(--glass-border);">
            {{ $summaries->links() }}
        </div>
        @endif

        @else
        <div class="ptx-empty-state">
            <i class="bi bi-calendar-x d-block" style="font-size: 2rem; margin-bottom: 0.75rem;"></i>
            <p>No daily summaries yet. They are compiled automatically each day at 00:05.</p>
        </div>
        @endif
    </div>
</div>
@endsection
