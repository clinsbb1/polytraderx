@extends('layouts.admin')

@section('title', 'Audit #' . $audit->id)

@section('content')
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Audit Detail — #{{ $audit->id }}</h5>
    </div>
    <div class="ptx-card-body">
        <p style="color: var(--text-secondary);">Audit analysis and suggested fixes will be displayed here.</p>
    </div>
</div>
@endsection
