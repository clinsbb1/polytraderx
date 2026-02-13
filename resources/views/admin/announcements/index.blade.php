@extends('layouts.super-admin')

@section('title', 'Announcements')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Announcements</h5>
    <a href="/admin/announcements/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Create Announcement
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th class="text-center">Active</th>
                        <th class="text-center">Dashboard</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $a)
                    <tr>
                        <td class="fw-semibold">{{ $a->title }}</td>
                        <td>
                            @php
                                $typeColors = ['info' => 'info', 'warning' => 'warning', 'success' => 'success', 'danger' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$a->type] ?? 'secondary' }}">{{ ucfirst($a->type) }}</span>
                        </td>
                        <td class="text-center">
                            @if($a->is_active)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($a->show_on_dashboard)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $a->created_at->format('M j, Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/admin/announcements/{{ $a->id }}/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/admin/announcements/{{ $a->id }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-megaphone fs-3 d-block mb-2"></i>
                            No announcements yet. Create your first one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $announcements->links() }}</div>
@endsection
