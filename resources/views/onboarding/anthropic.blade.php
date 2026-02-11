@extends('layouts.public')

@section('title', 'AI Setup — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:600px">
        <!-- Progress -->
        <div class="d-flex justify-content-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-primary rounded-pill px-3">4</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">5</span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="fw-bold mb-3">Anthropic AI Key</h2>
                <p class="text-muted">Add your Anthropic API key to enable AI-powered market analysis (Muscles + Brain tiers).</p>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i> Get your API key from <strong>console.anthropic.com</strong>. The bot uses Claude Haiku (cheap, frequent) and Claude Sonnet (expensive, on-demand forensics).
                </div>

                <form method="POST" action="/onboarding/anthropic">
                    @csrf

                    <div class="mb-3">
                        <label for="anthropic_api_key" class="form-label fw-semibold">Anthropic API Key</label>
                        <input type="password" id="anthropic_api_key" name="anthropic_api_key" class="form-control" placeholder="sk-ant-..." value="{{ old('anthropic_api_key') }}">
                        @if($credential && $credential->anthropic_api_key)
                            <div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Key already saved</div>
                        @endif
                        @error('anthropic_api_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i> Without an AI key, the bot will run in Reflexes-only mode (rule-based, no AI scoring). You can add it later in settings.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/onboarding/telegram" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <div>
                            <a href="/onboarding/activate" class="btn btn-outline-secondary me-2">Skip</a>
                            <button type="submit" class="btn btn-ptx">Next: Review <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
