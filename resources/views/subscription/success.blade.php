@extends('layouts.admin')

@section('title', 'Payment Successful')

@section('content')
<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
    </div>
    <h2 class="fw-bold">Payment Received!</h2>
    <p class="text-muted mb-4">Your subscription will be activated once the payment is confirmed on the blockchain. This usually takes a few minutes.</p>
    <a href="/dashboard" class="btn btn-primary">Go to Dashboard</a>
</div>
@endsection
