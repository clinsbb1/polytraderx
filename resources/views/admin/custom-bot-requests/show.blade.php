@extends('layouts.super-admin')

@section('title', 'Custom Bot Request #' . $botRequest->id)

@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.custom-bot-requests.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Requests
    </a>
    <h5 class="mb-0">Request from {{ $botRequest->name }}</h5>
    @php
        $statusColors = [
            'pending'   => 'warning',
            'reviewing' => 'info',
            'accepted'  => 'success',
            'declined'  => 'danger',
        ];
    @endphp
    <span class="badge bg-{{ $statusColors[$botRequest->status] ?? 'secondary' }} fs-6">{{ ucfirst($botRequest->status) }}</span>
</div>

<div class="row g-4">
    <!-- Left: Request Details -->
    <div class="col-lg-8">

        <!-- Requester Info -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-person me-2"></i>Requester
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Name</div>
                        <div>{{ $botRequest->name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Email</div>
                        <div><a href="mailto:{{ $botRequest->email }}">{{ $botRequest->email }}</a></div>
                    </div>
                    @if($botRequest->contact)
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Additional Contact</div>
                        <div>{{ $botRequest->contact }}</div>
                    </div>
                    @endif
                    @if($botRequest->user)
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Platform Account</div>
                        <div>
                            <a href="/admin/users/{{ $botRequest->user->id }}" class="text-decoration-none">
                                {{ $botRequest->user->name }}
                            </a>
                            <span class="text-muted small ms-1">({{ $botRequest->user->account_id ?? '#'.$botRequest->user_id }})</span>
                        </div>
                    </div>
                    @endif
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Submitted</div>
                        <div>{{ $botRequest->created_at->format('M j, Y g:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategy -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-graph-up me-2"></i>Strategy Details
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small mb-1">Strategy Summary</div>
                    <div class="bg-light rounded p-3" style="white-space: pre-wrap;">{{ $botRequest->strategy_summary }}</div>
                </div>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted small mb-1">Markets</div>
                        <div>{{ $botRequest->markets ?: '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small mb-1">Timeframe</div>
                        <div>{{ $botRequest->timeframe ?: '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small mb-1">Wants AI</div>
                        <div>
                            @if($botRequest->wants_ai)
                                <span class="badge bg-primary">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($botRequest->risk_limits_json && array_filter((array) $botRequest->risk_limits_json))
                <div class="mt-3">
                    <div class="text-muted small mb-2">Risk Limits</div>
                    <div class="row g-2">
                        @if(!empty($botRequest->risk_limits_json['max_bet']))
                        <div class="col-sm-6">
                            <div class="border rounded p-2 small">
                                <span class="text-muted">Max Bet:</span>
                                <strong>${{ number_format((float) $botRequest->risk_limits_json['max_bet'], 2) }}</strong>
                            </div>
                        </div>
                        @endif
                        @if(!empty($botRequest->risk_limits_json['daily_loss']))
                        <div class="col-sm-6">
                            <div class="border rounded p-2 small">
                                <span class="text-muted">Max Daily Loss:</span>
                                <strong>${{ number_format((float) $botRequest->risk_limits_json['daily_loss'], 2) }}</strong>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Budget & Timeline -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-currency-dollar me-2"></i>Budget & Timeline
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Budget Range</div>
                        <div class="fw-semibold">{{ $botRequest->budget_range ?: '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Timeline</div>
                        <div class="fw-semibold">{{ $botRequest->timeline ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($botRequest->notes)
        <!-- Additional Notes -->
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-chat-text me-2"></i>Additional Notes
            </div>
            <div class="card-body">
                <div class="bg-light rounded p-3" style="white-space: pre-wrap;">{{ $botRequest->notes }}</div>
            </div>
        </div>
        @endif

    </div>

    <!-- Right: Actions -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 1rem;">
            <div class="card-header fw-semibold">
                <i class="bi bi-pencil-square me-2"></i>Update Status
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.custom-bot-requests.status', $botRequest) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['pending', 'reviewing', 'accepted', 'declined'] as $s)
                                <option value="{{ $s }}" {{ $botRequest->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Setting to <strong>Accepted</strong> or <strong>Declined</strong> sends an email to the user.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Message to User <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="admin_notes" class="form-control form-control-sm" rows="4"
                            placeholder="Include a personal note — shown in the email to the user."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i>Update Status
                    </button>
                </form>

                <hr class="my-3">

                <div class="small text-muted">
                    <div class="mb-1"><strong>Status guide:</strong></div>
                    <div class="mb-1"><span class="badge bg-warning text-dark">Pending</span> — Awaiting review</div>
                    <div class="mb-1"><span class="badge bg-info">Reviewing</span> — Under evaluation (no email)</div>
                    <div class="mb-1"><span class="badge bg-success">Accepted</span> — Notify user: project proceeding</div>
                    <div><span class="badge bg-danger">Declined</span> — Notify user: not accepted</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
