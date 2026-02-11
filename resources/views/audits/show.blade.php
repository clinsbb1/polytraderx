@extends('layouts.admin')

@section('title', 'Audit #' . $audit->id)

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Audit Detail — #{{ $audit->id }}</h5>
    </div>
    <div class="card-body">
        <p>Audit analysis and suggested fixes will be displayed here.</p>
    </div>
</div>
@endsection
