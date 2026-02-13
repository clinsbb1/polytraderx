@extends('layouts.super-admin')

@section('title', 'Subscription Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Subscription Plans</h5>
    <a href="/admin/plans/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Create Plan
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Price</th>
                        <th>Billing Period</th>
                        <th class="text-center">Max Daily Trades</th>
                        <th class="text-center">Max Positions</th>
                        <th class="text-center">AI Muscles</th>
                        <th class="text-center">AI Brain</th>
                        <th>Active</th>
                        <th class="text-center">Sort</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                    <tr>
                        <td class="fw-semibold">{{ $plan->name }}</td>
                        <td><code>{{ $plan->slug }}</code></td>
                        <td>${{ number_format((float)$plan->price_usd, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $plan->billing_period === 'yearly' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($plan->billing_period) }}
                            </span>
                        </td>
                        <td class="text-center">{{ $plan->max_daily_trades ?: 'Unlimited' }}</td>
                        <td class="text-center">{{ $plan->max_concurrent_positions ?: 'Unlimited' }}</td>
                        <td class="text-center">
                            @if($plan->has_ai_muscles)
                                <span class="badge bg-info">Yes</span>
                            @else
                                <span class="badge bg-light text-muted">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($plan->has_ai_brain)
                                <span class="badge bg-warning text-dark">Yes</span>
                            @else
                                <span class="badge bg-light text-muted">No</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-center text-muted">{{ $plan->sort_order ?? 0 }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/admin/plans/{{ $plan->id }}/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/admin/plans/{{ $plan->id }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this plan?')">
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
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bi bi-box fs-3 d-block mb-2"></i>
                            No subscription plans found. Create your first plan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
