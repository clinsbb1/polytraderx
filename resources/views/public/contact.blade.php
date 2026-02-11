@extends('layouts.public')

@section('title', 'Contact — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:700px">
        <h1 class="fw-bold mb-4 text-center">Contact Us</h1>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4">
                    <h5><i class="bi bi-envelope me-2 text-primary"></i>Email Support</h5>
                    <p class="text-muted">For general inquiries, billing questions, or technical support, email us at:</p>
                    <p><strong>support@polytraderx.com</strong></p>
                </div>

                <hr>

                <div class="mb-4">
                    <h5><i class="bi bi-telegram me-2 text-primary"></i>Telegram</h5>
                    <p class="text-muted">Join our Telegram community for real-time support and updates.</p>
                </div>

                <hr>

                <div class="mb-4">
                    <h5><i class="bi bi-clock me-2 text-primary"></i>Response Time</h5>
                    <p class="text-muted">We aim to respond to all inquiries within 24 hours during business days.</p>
                </div>

                <hr>

                <div>
                    <h5><i class="bi bi-bug me-2 text-primary"></i>Bug Reports</h5>
                    <p class="text-muted">Found a bug? Please include your user ID (visible in your dashboard settings), a description of the issue, and any relevant screenshots or error messages.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
