@extends('layouts.super-admin')

@section('title', 'Subscription Plans')

@section('content')
<div class="d-flex justify-content-between mb-4">
    <h5 class="mb-0">Subscription Plans</h5>
    <a href="/admin/plans/create" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Plan</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Slug</th><th>Name</th><th>Price</th><th>Period</th><th>Trades/Day</th><th>AI</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                <tr>
                    <td><code>{{ $plan->slug }}</code></td>
                    <td>{{ $plan->name }}</td>
                    <td>${{ number_format((float)$plan->price_usd, 2) }}</td>
                    <td>{{ $plan->billing_period }}</td>
                    <td>{{ $plan->max_daily_trades ?: 'Unlimited' }}</td>
                    <td>
                        @if($plan->has_ai_muscles) <span class="badge bg-info">M</span> @endif
                        @if($plan->has_ai_brain) <span class="badge bg-warning">B</span> @endif
                    </td>
                    <td><span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <a href="/admin/plans/{{ $plan->id }}/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="/admin/plans/{{ $plan->id }}" class="d-inline" onsubmit="return confirm('Delete this plan?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
