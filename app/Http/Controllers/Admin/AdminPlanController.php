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
            'max_signals_per_day' => ['nullable', 'integer', 'min:0'],
            'max_concurrent_positions' => ['nullable', 'integer', 'min:0'],
            'ai_monthly_token_cap' => ['nullable', 'integer', 'min:0'],
            'ai_brain_calls_per_day' => ['nullable', 'integer', 'min:0'],
            'ai_muscles_calls_per_day' => ['nullable', 'integer', 'min:0'],
            'ai_max_tokens_per_request' => ['nullable', 'integer', 'min:1000'],
            'historical_days' => ['nullable', 'integer', 'min:0'],
            'ai_muscles_enabled' => ['boolean'],
            'ai_brain_enabled' => ['boolean'],
            'csv_export_enabled' => ['boolean'],
            'strategy_health_metrics' => ['boolean'],
            'telegram_enabled' => ['boolean'],
            'priority_processing' => ['boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'lifetime_cap' => ['nullable', 'integer', 'min:0'],
            'lifetime_sold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['ai_muscles_enabled'] = $request->boolean('ai_muscles_enabled');
        $validated['ai_brain_enabled'] = $request->boolean('ai_brain_enabled');
        $validated['csv_export_enabled'] = $request->boolean('csv_export_enabled');
        $validated['strategy_health_metrics'] = $request->boolean('strategy_health_metrics');
        $validated['telegram_enabled'] = $request->boolean('telegram_enabled');
        $validated['priority_processing'] = $request->boolean('priority_processing');
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
            'max_signals_per_day' => ['nullable', 'integer', 'min:0'],
            'max_concurrent_positions' => ['nullable', 'integer', 'min:0'],
            'ai_monthly_token_cap' => ['nullable', 'integer', 'min:0'],
            'ai_brain_calls_per_day' => ['nullable', 'integer', 'min:0'],
            'ai_muscles_calls_per_day' => ['nullable', 'integer', 'min:0'],
            'ai_max_tokens_per_request' => ['nullable', 'integer', 'min:1000'],
            'historical_days' => ['nullable', 'integer', 'min:0'],
            'ai_muscles_enabled' => ['boolean'],
            'ai_brain_enabled' => ['boolean'],
            'csv_export_enabled' => ['boolean'],
            'strategy_health_metrics' => ['boolean'],
            'telegram_enabled' => ['boolean'],
            'priority_processing' => ['boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'lifetime_cap' => ['nullable', 'integer', 'min:0'],
            'lifetime_sold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['ai_muscles_enabled'] = $request->boolean('ai_muscles_enabled');
        $validated['ai_brain_enabled'] = $request->boolean('ai_brain_enabled');
        $validated['csv_export_enabled'] = $request->boolean('csv_export_enabled');
        $validated['strategy_health_metrics'] = $request->boolean('strategy_health_metrics');
        $validated['telegram_enabled'] = $request->boolean('telegram_enabled');
        $validated['priority_processing'] = $request->boolean('priority_processing');
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
