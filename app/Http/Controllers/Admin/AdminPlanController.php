<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::ordered()->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'unique:subscription_plans'],
            'name' => ['required', 'string', 'max:100'],
            'price_usd' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:monthly,yearly'],
            'max_daily_trades' => ['nullable', 'integer', 'min:0'],
            'max_concurrent_positions' => ['nullable', 'integer', 'min:0'],
            'has_ai_muscles' => ['boolean'],
            'has_ai_brain' => ['boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['has_ai_muscles'] = $request->boolean('has_ai_muscles');
        $validated['has_ai_brain'] = $request->boolean('has_ai_brain');
        $validated['is_active'] = $request->boolean('is_active', true);

        SubscriptionPlan::create($validated);

        return redirect('/admin/plans')->with('success', 'Plan created.');
    }

    public function edit(SubscriptionPlan $plan): View
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'unique:subscription_plans,slug,' . $plan->id],
            'name' => ['required', 'string', 'max:100'],
            'price_usd' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:monthly,yearly'],
            'max_daily_trades' => ['nullable', 'integer', 'min:0'],
            'max_concurrent_positions' => ['nullable', 'integer', 'min:0'],
            'has_ai_muscles' => ['boolean'],
            'has_ai_brain' => ['boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['has_ai_muscles'] = $request->boolean('has_ai_muscles');
        $validated['has_ai_brain'] = $request->boolean('has_ai_brain');
        $validated['is_active'] = $request->boolean('is_active', true);

        $plan->update($validated);

        return redirect('/admin/plans')->with('success', 'Plan updated.');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect('/admin/plans')->with('success', 'Plan deleted.');
    }
}
