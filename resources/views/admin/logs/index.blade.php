@extends('layouts.super-admin')

@section('title', 'Trade Logs')

@section('content')
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="number" name="user_id" class="form-control" placeholder="User ID" value="{{ request('user_id') }}">
            </div>
            <div class="col-md-3">
                <select name="level" class="form-select">
                    <option value="">All Levels</option>
                    @foreach(['info','warning','error','debug'] as $l)
                        <option value="{{ $l }}" {{ request('level') === $l ? 'selected' : '' }}>{{ ucfirst($l) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr><th>Time</th><th>User</th><th>Trade</th><th>Level</th><th>Event</th><th>Message</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="small text-muted text-nowrap">{{ $log->created_at->format('M j H:i:s') }}</td>
                    <td>{{ $log->trade->user->name ?? 'N/A' }}</td>
                    <td>{{ $log->trade_id }}</td>
                    <td><span class="badge bg-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'secondary') }}">{{ $log->level }}</span></td>
                    <td class="small">{{ $log->event }}</td>
                    <td class="small">{{ \Illuminate\Support\Str::limit($log->message, 100) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No logs found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $logs->links() }}</div>
@endsection
