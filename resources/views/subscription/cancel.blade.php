@extends('layouts.admin')

@section('title', 'Payment Cancelled')

@section('content')
<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-x-circle-fill text-warning" style="font-size: 4rem;"></i>
    </div>
    <h2 class="fw-bold">Payment Cancelled</h2>
    <p class="text-muted mb-4">Your payment was cancelled. No charges have been made.</p>
    <div class="d-flex justify-content-center gap-3">
        <a href="/subscription" class="btn btn-primary">Try Again</a>
        <a href="/dashboard" class="btn btn-outline-secondary">Go to Dashboard</a>
    </div>
</div>
@endsection
