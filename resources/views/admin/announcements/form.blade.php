@extends('layouts.super-admin')

@section('title', $announcement ? 'Edit Announcement' : 'Create Announcement')

@section('content')
<div class="mb-3">
    <a href="/admin/announcements" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Announcements
    </a>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-header">
        <h6 class="mb-0">{{ $announcement ? 'Edit Announcement' : 'Create New Announcement' }}</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $announcement ? '/admin/announcements/' . $announcement->id : '/admin/announcements' }}">
            @csrf
            @if($announcement) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $announcement->title ?? '') }}" required placeholder="Announcement title">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Body <span class="text-danger">*</span></label>
                <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="6" required placeholder="Announcement content...">{{ old('body', $announcement->body ?? '') }}</textarea>
                @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Type</label>
                <select name="type" class="form-select @error('type') is-invalid @enderror">
                    @foreach(['info' => 'Info', 'warning' => 'Warning', 'success' => 'Success', 'danger' => 'Danger'] as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $announcement->type ?? 'info') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                        {{ old('is_active', $announcement->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
                <div class="form-text">Only active announcements are visible to users.</div>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="show_on_dashboard" value="1" id="showOnDashboard"
                        {{ old('show_on_dashboard', $announcement->show_on_dashboard ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="showOnDashboard">Show on Dashboard</label>
                </div>
                <div class="form-text">If checked, this announcement will appear on the user dashboard.</div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/admin/announcements" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>{{ $announcement ? 'Update Announcement' : 'Create Announcement' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
