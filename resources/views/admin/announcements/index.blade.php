@extends('layouts.super-admin')

@section('title', 'Announcements')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h5 class="mb-0">Announcements</h5>
    <a href="/admin/announcements/create" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Announcement</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Title</th><th>Type</th><th>Active</th><th>Dashboard</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($announcements as $a)
                <tr>
                    <td>{{ $a->title }}</td>
                    <td><span class="badge bg-{{ $a->type }}">{{ $a->type }}</span></td>
                    <td><i class="bi {{ $a->is_active ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }}"></i></td>
                    <td><i class="bi {{ $a->show_on_dashboard ? 'bi-check-circle text-success' : 'bi-x-circle text-muted' }}"></i></td>
                    <td class="small text-muted">{{ $a->created_at->format('M j, Y') }}</td>
                    <td>
                        <a href="/admin/announcements/{{ $a->id }}/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="/admin/announcements/{{ $a->id }}" class="d-inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No announcements</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $announcements->links() }}</div>
@endsection
