@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Today's P&L</h6>
                <h2 class="text-success">$0.00</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Win Rate (Today)</h6>
                <h2>0%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Balance</h6>
                <h2>$100.00</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">Open Positions</h6>
                <h2>0</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Trades</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">No trades yet. The bot will populate this when it starts trading.</p>
            </div>
        </div>
    </div>
</div>
@endsection
