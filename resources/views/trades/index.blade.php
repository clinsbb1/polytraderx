@extends('layouts.admin')

@section('title', 'Trades')

@section('content')
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>All Trades</h5>
    </div>
    <div class="ptx-card-body p-0">
        <div class="ptx-empty-state">
            <i class="bi bi-currency-exchange d-block"></i>
            <p>No trades recorded yet.</p>
        </div>
    </div>
</div>
@endsection
