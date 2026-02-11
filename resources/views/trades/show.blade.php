@extends('layouts.admin')

@section('title', 'Trade #' . $trade->id)

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Trade Detail — #{{ $trade->id }}</h5>
    </div>
    <div class="card-body">
        <p>Trade details will be displayed here.</p>
    </div>
</div>
@endsection
