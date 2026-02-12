@extends('layouts.admin')

@section('title', 'Trade Logs')

@section('content')
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Trade Logs</h5>
    </div>
    <div class="ptx-card-body p-0">
        <div class="ptx-empty-state">
            <i class="bi bi-journal-text d-block"></i>
            <p>No log entries yet. Trade logs will appear here with full forensic data.</p>
        </div>
    </div>
</div>
@endsection
