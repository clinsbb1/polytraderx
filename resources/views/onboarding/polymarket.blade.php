@extends('layouts.public')

@section('title', 'Polymarket Setup — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:600px">
        <!-- Progress -->
        <div class="d-flex justify-content-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-primary rounded-pill px-3">2</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">3</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">4</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">5</span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="fw-bold mb-3">Polymarket API Keys</h2>
                <p class="text-muted">Connect your Polymarket account so the bot can place trades. Your keys are encrypted at rest.</p>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i> Get your API keys from your Polymarket account settings. You need CLOB API access.
                </div>

                <form method="POST" action="/onboarding/polymarket">
                    @csrf

                    <div class="mb-3">
                        <label for="polymarket_api_key" class="form-label fw-semibold">API Key</label>
                        <input type="password" id="polymarket_api_key" name="polymarket_api_key" class="form-control" placeholder="Enter your Polymarket API key" value="{{ old('polymarket_api_key') }}">
                        @if($credential && $credential->polymarket_api_key)
                            <div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Key already saved</div>
                        @endif
                        @error('polymarket_api_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="polymarket_api_secret" class="form-label fw-semibold">API Secret</label>
                        <input type="password" id="polymarket_api_secret" name="polymarket_api_secret" class="form-control" placeholder="Enter your Polymarket API secret" value="{{ old('polymarket_api_secret') }}">
                        @if($credential && $credential->polymarket_api_secret)
                            <div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Secret already saved</div>
                        @endif
                        @error('polymarket_api_secret') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="polymarket_passphrase" class="form-label fw-semibold">Passphrase</label>
                        <input type="password" id="polymarket_passphrase" name="polymarket_passphrase" class="form-control" placeholder="Enter your Polymarket passphrase" value="{{ old('polymarket_passphrase') }}">
                        @if($credential && $credential->polymarket_passphrase)
                            <div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Passphrase already saved</div>
                        @endif
                        @error('polymarket_passphrase') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/onboarding" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <div>
                            <a href="/onboarding/telegram" class="btn btn-outline-secondary me-2">Skip</a>
                            <button type="submit" class="btn btn-ptx">Next: Telegram <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
