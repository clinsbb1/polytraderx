<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StrategyParam;
use App\Models\User;
use App\Services\Email\LifecycleEmailService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('account_id', 'like', "%{$search}%");
            });
        }

        if ($plan = $request->get('plan')) {
            $query->where('subscription_plan', $plan);
        }

        if ($request->get('active') !== null && $request->get('active') !== '') {
            $query->where('is_active', $request->boolean('active'));
        }

        $users = $query
            ->addSelect([
                'simulator_enabled' => StrategyParam::query()
                    ->select('value')
                    ->whereColumn('strategy_params.user_id', 'users.id')
                    ->where('key', 'SIMULATOR_ENABLED')
                    ->limit(1),
            ])
            ->withCount('trades')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load('payments');
        $tradeStats = [
            'total' => $user->trades()->count(),
            'won' => $user->trades()->won()->count(),
            'lost' => $user->trades()->lost()->count(),
            'open' => $user->trades()->open()->count(),
            'total_pnl' => (float) $user->trades()->whereNotNull('pnl')->sum('pnl'),
        ];

        $recentTrades = $user->trades()->latest()->take(20)->get();

        return view('admin.users.show', compact('user', 'tradeStats', 'recentTrades'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function changePlan(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'subscription_plan' => ['required', 'string', 'in:free,pro,advanced,lifetime'],
        ]);

        $user->update([
            'subscription_plan' => $request->subscription_plan,
            'subscription_ends_at' => $request->subscription_plan !== 'free'
                ? now()->addDays(30)
                : null,
        ]);

        return back()->with('success', "User plan changed to {$request->subscription_plan}.");
    }

    public function grantFreeSubscription(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'plan_slug' => ['required', 'string', 'in:free,pro,advanced,lifetime'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $subscriptionService = app(SubscriptionService::class);

        $subscriptionService->grantFreeSubscription(
            $user->id,
            $request->plan_slug,
            $request->integer('duration_days'),
            auth()->id(),
        );

        $plan = $subscriptionService->getPlanBySlug($request->plan_slug);
        if ($plan) {
            $durationDays = $plan->slug === 'lifetime' ? null : $request->integer('duration_days');

            app(LifecycleEmailService::class)->sendFreeSubscriptionGranted(
                $user->fresh(),
                $plan,
                $durationDays
            );
        }

        $durationLabel = $request->plan_slug === 'lifetime'
            ? 'lifetime access'
            : "{$request->duration_days} days";

        return back()->with('success', "Free {$request->plan_slug} subscription granted to {$user->name} ({$durationLabel}).");
    }

    public function impersonate(User $user): RedirectResponse
    {
        $admin = auth()->user();

        if (!$admin || !$admin->isSuperAdmin()) {
            abort(403, 'Unauthorized. Super admin access required.');
        }

        session()->put('impersonator_id', $admin->id);
        Auth::login($user);
        request()->session()->regenerate();

        return redirect('/dashboard');
    }

    public function stopImpersonating(): RedirectResponse
    {
        $adminId = session()->pull('impersonator_id');

        if (!$adminId) {
            return redirect('/dashboard')->with('error', 'Impersonation session not found.');
        }

        $admin = User::query()->find($adminId);
        if (!$admin || !$admin->isSuperAdmin()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/login')->with('error', 'Original admin session is no longer valid.');
        }

        Auth::loginUsingId($adminId);
        request()->session()->regenerate();

        return redirect('/admin');
    }
}
