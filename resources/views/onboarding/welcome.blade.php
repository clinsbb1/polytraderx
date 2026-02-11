@extends('layouts.public')

@section('title', 'Welcome — PolyTraderX Setup')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:600px">
        <!-- Progress -->
        <div class="d-flex justify-content-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-3">1</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">2</span>
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
                <h2 class="fw-bold mb-3">Welcome to PolyTraderX!</h2>
                <p class="text-muted">Let's get your trading bot set up. This will only take a few minutes.</p>

                <form method="POST" action="/onboarding">
                    @csrf

                    <div class="mb-3">
                        <label for="timezone" class="form-label fw-semibold">Your Timezone</label>
                        <select id="timezone" name="timezone" class="form-select" required>
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ (auth()->user()->timezone ?? 'Africa/Lagos') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-ptx">Next: Polymarket Keys <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
