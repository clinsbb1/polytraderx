@extends('layouts.admin')

@section('title', 'Balance & Equity')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Equity Curve</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Balance chart will be rendered here with Chart.js once balance snapshots are recorded.</p>
        <canvas id="equityChart" height="100"></canvas>
    </div>
</div>
@endsection
