@extends('layouts.admin')

@section('title', 'Balance & Equity')

@section('content')
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Equity Curve</h5>
    </div>
    <div class="ptx-card-body">
        <div class="ptx-empty-state">
            <i class="bi bi-graph-up d-block"></i>
            <p>Balance chart will be rendered here once balance snapshots are recorded.</p>
        </div>
        <canvas id="equityChart" height="100"></canvas>
    </div>
</div>
@endsection
