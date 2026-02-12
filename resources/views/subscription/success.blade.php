@extends('layouts.admin')

@section('title', 'Payment Successful')

@section('content')
<div class="ptx-result-page">
    <div class="result-icon success">
        <i class="bi bi-check-circle-fill"></i>
    </div>
    <h2>Payment Received!</h2>
    <p>Your subscription will be activated once the payment is confirmed on the blockchain. This usually takes a few minutes.</p>
    <a href="/dashboard" class="btn-ptx-primary">Go to Dashboard</a>
</div>
@endsection
