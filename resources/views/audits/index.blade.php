@extends('layouts.admin')

@section('title', 'AI Audits')

@section('content')
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>AI Audit Reports</h5>
    </div>
    <div class="ptx-card-body p-0">
        <div class="ptx-empty-state">
            <i class="bi bi-robot d-block"></i>
            <p>No audits recorded yet.</p>
        </div>
    </div>
</div>
@endsection
