@extends('layouts.super-admin')

@section('title', $announcement ? 'Edit Announcement' : 'Create Announcement')

@section('content')
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="{{ $announcement ? '/admin/announcements/' . $announcement->id : '/admin/announcements' }}">
            @csrf
            @if($announcement) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title ?? '') }}" required>
                @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Body</label>
                <textarea name="body" class="form-control" rows="5" required>{{ old('body', $announcement->body ?? '') }}</textarea>
                @error('body') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Type</label>
                <select name="type" class="form-select">
                    @foreach(['info','warning','success','danger'] as $type)
                        <option value="{{ $type }}" {{ old('type', $announcement->type ?? 'info') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $announcement->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">Active</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="show_on_dashboard" value="1" {{ old('show_on_dashboard', $announcement->show_on_dashboard ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">Show on Dashboard</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/admin/announcements" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $announcement ? 'Update' : 'Create' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
