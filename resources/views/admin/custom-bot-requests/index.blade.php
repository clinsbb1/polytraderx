@extends('layouts.super-admin')

@section('title', 'Custom Bot Requests')

@section('content')
<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending</div>
                    <div class="fw-bold fs-4">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-eye"></i>
                </div>
                <div>
                    <div class="text-muted small">Reviewing</div>
                    <div class="fw-bold fs-4">{{ $stats['reviewing'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Accepted</div>
                    <div class="fw-bold fs-4">{{ $stats['accepted'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Declined</div>
                    <div class="fw-bold fs-4">{{ $stats['declined'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['pending', 'reviewing', 'accepted', 'declined'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
            @if(request('status'))
                <div class="col-md-2">
                    <a href="{{ route('admin.custom-bot-requests.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Submitted</th>
                        <th>User</th>
                        <th>Name / Contact</th>
                        <th>Budget</th>
                        <th>Timeline</th>
                        <th>AI?</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $botRequest)
                    @php
                        $statusColors = [
                            'pending'   => 'warning',
                            'reviewing' => 'info',
                            'accepted'  => 'success',
                            'declined'  => 'danger',
                        ];
                    @endphp
                    <tr>
                        <td class="ps-3 small text-muted text-nowrap">{{ $botRequest->created_at->format('M j, Y') }}</td>
                        <td>
                            @if($botRequest->user)
                                <a href="/admin/users/{{ $botRequest->user->id }}" class="text-decoration-none">
                                    {{ $botRequest->user->name }}
                                </a>
                                <div class="small text-muted">{{ $botRequest->user->email }}</div>
                            @else
                                <span class="text-muted small">Deleted user</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold small">{{ $botRequest->name }}</div>
                            @if($botRequest->contact)
                                <div class="small text-muted">{{ $botRequest->contact }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $botRequest->budget_range ?: '—' }}</td>
                        <td class="small">{{ $botRequest->timeline ?: '—' }}</td>
                        <td>
                            @if($botRequest->wants_ai)
                                <span class="badge bg-primary">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusColors[$botRequest->status] ?? 'secondary' }}">{{ ucfirst($botRequest->status) }}</span>
                        </td>
                        <td class="pe-3">
                            <a href="{{ route('admin.custom-bot-requests.show', $botRequest) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-robot fs-3 d-block mb-2 opacity-50"></i>
                            No custom bot requests yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $requests->withQueryString()->links() }}</div>
@endsection
