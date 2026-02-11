@extends('layouts.public')

@section('title', 'Activate — PolyTraderX')

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
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-primary rounded-pill px-3">5</span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="fw-bold mb-3">Review & Activate</h2>
                <p class="text-muted">Here's a summary of your setup. You can change everything later in settings.</p>

                <table class="table">
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Timezone</td>
                            <td>{{ $user->timezone ?? 'Not set' }}</td>
                            <td>
                                @if($user->timezone)
                                    <span class="badge bg-success">Set</span>
                                @else
                                    <span class="badge bg-warning">Missing</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Polymarket Keys</td>
                            <td>{{ $credential && $credential->hasPolymarketKeys() ? 'Configured' : 'Not configured' }}</td>
                            <td>
                                @if($credential && $credential->hasPolymarketKeys())
                                    <span class="badge bg-success">Ready</span>
                                @else
                                    <span class="badge bg-danger">Required for live trading</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Telegram</td>
                            <td>{{ $credential && $credential->hasTelegramKeys() ? 'Configured' : 'Not configured' }}</td>
                            <td>
                                @if($credential && $credential->hasTelegramKeys())
                                    <span class="badge bg-success">Ready</span>
                                @else
                                    <span class="badge bg-secondary">Optional</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Anthropic AI</td>
                            <td>{{ $credential && $credential->hasAnthropicKey() ? 'Configured' : 'Not configured' }}</td>
                            <td>
                                @if($credential && $credential->hasAnthropicKey())
                                    <span class="badge bg-success">Ready</span>
                                @else
                                    <span class="badge bg-secondary">Optional</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Subscription</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $user->subscription_plan ?? 'free_trial')) }}</td>
                            <td>
                                @if($user->trial_ends_at)
                                    <span class="badge bg-info">{{ $user->daysLeftInTrial() }} days left</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                @if(!$credential || !$credential->hasPolymarketKeys())
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i> Without Polymarket keys, the bot will start in <strong>DRY_RUN (paper trading)</strong> mode. Add keys later in Settings to enable live trading.
                    </div>
                @endif

                <form method="POST" action="/onboarding/activate">
                    @csrf
                    <div class="d-flex justify-content-between">
                        <a href="/onboarding/anthropic" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-ptx btn-lg">
                            <i class="bi bi-rocket-takeoff me-1"></i> Activate My Bot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
