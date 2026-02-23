@extends('layouts.super-admin')

@section('title', 'Telegram Messages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Telegram Admin Messaging</h5>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Send Message</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.telegram.messages.send') }}" enctype="multipart/form-data" id="telegramSendForm">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Target</label>
                    <select name="target" id="targetSelect" class="form-select" required>
                        <option value="all" {{ old('target') === 'all' ? 'selected' : '' }}>All connected users</option>
                        <option value="paid_active" {{ old('target') === 'paid_active' ? 'selected' : '' }}>Paid users (active)</option>
                        <option value="free_plan" {{ old('target') === 'free_plan' ? 'selected' : '' }}>Unpaid users (free plan)</option>
                        <option value="single" {{ old('target') === 'single' ? 'selected' : '' }}>Single connected user</option>
                    </select>
                </div>
                <div class="col-md-8" id="singleRecipientWrap" style="display: none;">
                    <label class="form-label fw-semibold">Recipient</label>
                    <select name="recipient_user_id" class="form-select">
                        <option value="">Select connected user</option>
                        @foreach($connectedUsers as $u)
                            <option value="{{ $u->id }}" {{ (string) old('recipient_user_id') === (string) $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->account_id }}){{ $u->telegram_username ? ' @' . $u->telegram_username : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Message</label>
                <textarea name="message" class="form-control" rows="5" maxlength="4000" required>{{ old('message') }}</textarea>
                <div class="form-text">HTML formatting is supported by Telegram. Keep it concise for better delivery.</div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Image (optional)</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                <div class="form-text">If provided, image will be sent with your message as caption.</div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Send Telegram Message
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Message History</h6>
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
                        <th>Message</th>
                        <th>Image</th>
                        <th>Status</th>
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
                                    <span class="text-muted small">{{ $item->recipient_chat_id ?: 'Unknown' }}</span>
                                @endif
                            </td>
                            <td style="max-width: 320px; white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($item->message, 180) }}</td>
                            <td>
                                @if($item->image_path)
                                    <a href="{{ asset('storage/' . $item->image_path) }}" target="_blank" rel="noopener noreferrer">View</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->status === 'pending')
                                    <span class="badge bg-warning text-dark">Queued</span>
                                @elseif($item->success)
                                    <span class="badge bg-success">Sent</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>
                            <td style="max-width: 240px;">{{ $item->error_message ? \Illuminate\Support\Str::limit($item->error_message, 120) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No Telegram messages sent yet.</td>
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

@section('scripts')
<script>
    (function () {
        const targetSelect = document.getElementById('targetSelect');
        const singleWrap = document.getElementById('singleRecipientWrap');

        function syncTargetUi() {
            const isSingle = targetSelect.value === 'single';
            singleWrap.style.display = isSingle ? '' : 'none';
        }

        targetSelect.addEventListener('change', syncTargetUi);
        syncTargetUi();
    })();
</script>
@endsection
