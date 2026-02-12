@extends('layouts.admin')

@section('title', 'Payment Cancelled')

@section('content')
<div class="ptx-result-page">
    <div class="result-icon warning">
        <i class="bi bi-x-circle-fill"></i>
    </div>
    <h2>Payment Cancelled</h2>
    <p>Your payment was cancelled. No charges have been made.</p>
    <div class="d-flex justify-content-center gap-3">
        <a href="/subscription" class="btn-ptx-primary">Try Again</a>
        <a href="/dashboard" class="btn-ptx-secondary">Go to Dashboard</a>
    </div>
</div>
@endsection
