@extends('layouts.super-admin')

@section('title', 'Email Messages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Announcement Email History</h5>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Queue & Delivery Status</h6>
        <span class="text-muted small">Newest first</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Queued At</th>
                        <th>Processed At</th>
                        <th>Admin</th>
                        <th>Target</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Announcement</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $item)
                        <tr>
                            <td class="small text-muted">{{ optional($item->created_at)->format('M j, Y H:i') }}</td>
                            <td class="small text-muted">{{ optional($item->sent_at)->format('M j, Y H:i') ?: '—' }}</td>
                            <td>{{ $item->admin?->name ?? 'N/A' }}</td>
                            <td>
                                @if($item->is_broadcast)
                                    <span class="badge bg-info">Broadcast</span>
                                @else
                                    <span class="badge bg-secondary">Single</span>
                                @endif
                            </td>
                            <td>
                                @if($item->recipient)
                                    {{ $item->recipient->name }}<br>
                                    <code class="small">{{ $item->recipient->account_id }}</code>
                                @else
                                    <span class="small text-muted">{{ $item->recipient_email }}</span>
                                @endif
                            </td>
                            <td style="max-width: 280px; white-space: normal;">{{ \Illuminate\Support\Str::limit($item->subject, 120) }}</td>
                            <td style="max-width: 220px; white-space: normal;">
                                @if($item->announcement)
                                    {{ \Illuminate\Support\Str::limit($item->announcement->title, 90) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->status === 'pending')
                                    <span class="badge bg-warning text-dark">Queued</span>
                                @elseif($item->status === 'processing')
                                    <span class="badge bg-primary">Processing</span>
                                @elseif($item->status === 'sent' || $item->success)
                                    <span class="badge bg-success">Sent</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>
                            <td>{{ (int) $item->attempts }}</td>
                            <td style="max-width: 280px; white-space: normal;">{{ $item->error_message ? \Illuminate\Support\Str::limit($item->error_message, 140) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No announcement emails queued yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $history->links('pagination::bootstrap-5') }}
</div>
@endsection
