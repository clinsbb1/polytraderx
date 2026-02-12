@extends('layouts.admin')

@section('title', 'Trade #' . $trade->id)

@section('content')
<div class="ptx-card">
    <div class="ptx-card-header">
        <h5>Trade Detail — #{{ $trade->id }}</h5>
    </div>
    <div class="ptx-card-body">
        <p style="color: var(--text-secondary);">Trade details will be displayed here.</p>
    </div>
</div>
@endsection
