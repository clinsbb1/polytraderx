<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTelegramMessage;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Email\LifecycleEmailService;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Subscription\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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

        if ($request->get('paid') !== null && $request->get('paid') !== '') {
            $isPaidFilter = $request->boolean('paid');
            if ($isPaidFilter) {
                $query->where(function ($q) {
                    $q->where('is_lifetime', true)
                      ->orWhere(function ($sub) {
                          $sub->whereIn('subscription_plan', ['pro', 'advanced', 'lifetime'])
                              ->where('subscription_ends_at', '>', now());
                      });
                });
            } else {
                $query->where('is_lifetime', false)
                      ->where(function ($q) {
                          $q->whereNotIn('subscription_plan', ['pro', 'advanced', 'lifetime'])
                            ->orWhereNull('subscription_ends_at')
                            ->orWhere('subscription_ends_at', '<=', now());
                      });
            }
        }

        $users = $query
            ->withCount('trades')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $planOptions = SubscriptionPlan::query()
            ->ordered()
            ->get(['slug', 'name', 'is_active']);

        return view('admin.users.index', compact('users', 'planOptions'));
    }

    public function trials(Request $request): View
    {
        $users = User::whereNotNull('pro_trial_used_at')
            ->withCount(['trades as trial_trades_count' => function ($q) {
                $q->whereColumn('created_at', '>=', 'pro_trial_used_at');
            }])
            ->latest('pro_trial_used_at')
            ->paginate(30)
            ->withQueryString();

        $activeCount = User::whereNotNull('pro_trial_used_at')
            ->where('billing_interval', 'trial')
            ->where('subscription_ends_at', '>', now())
            ->count();

        return view('admin.users.trials', compact('users', 'activeCount'));
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

        $availablePlans = SubscriptionPlan::query()
            ->ordered()
            ->get(['slug', 'name', 'billing_period', 'is_active']);

        return view('admin.users.show', compact('user', 'tradeStats', 'recentTrades', 'availablePlans'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function changePlan(Request $request, User $user): RedirectResponse
    {
        $planSlugs = SubscriptionPlan::query()->pluck('slug')->all();

        $request->validate([
            'subscription_plan' => ['required', 'string', Rule::in($planSlugs)],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $selectedPlan = SubscriptionPlan::query()
            ->where('slug', (string) $request->subscription_plan)
            ->firstOrFail();

        $isFreeSelection = $selectedPlan->slug === 'free';
        $freeModeEnabled = app(SubscriptionService::class)->isFreeModeEnabled();
        $configuredTrialDays = (int) ($selectedPlan->trial_days ?? 0);
        $freeTrialDays = $configuredTrialDays > 0
            ? $configuredTrialDays
            : app(PlatformSettingsService::class)->getInt('DEFAULT_TRIAL_DAYS', 7);

        $manualEndsAt = $request->filled('subscription_ends_at')
            ? Carbon::parse((string) $request->input('subscription_ends_at'))->endOfDay()
            : null;

        $user->update([
            'subscription_plan' => $selectedPlan->slug,
            'billing_interval' => $selectedPlan->billing_period === 'lifetime'
                ? 'lifetime'
                : ($isFreeSelection ? 'free' : 'monthly'),
            'is_lifetime' => $selectedPlan->slug === 'lifetime',
            'subscription_ends_at' => ($isFreeSelection || $selectedPlan->slug === 'lifetime')
                ? null
                : ($manualEndsAt ?? now()->addDays(30)),
            'trial_ends_at' => $isFreeSelection && $freeModeEnabled
                ? now()->addDays($freeTrialDays)
                : null,
            'is_active' => $isFreeSelection ? $freeModeEnabled : true,
        ]);

        return back()->with('success', "User plan changed to {$selectedPlan->name}.");
    }

    public function grantFreeSubscription(Request $request, User $user): RedirectResponse
    {
        $planSlugs = SubscriptionPlan::query()->pluck('slug')->all();

        $request->validate([
            'plan_slug' => ['required', 'string', Rule::in($planSlugs)],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $subscriptionService = app(SubscriptionService::class);

        if ($request->plan_slug === 'free' && !$subscriptionService->isFreeModeEnabled()) {
            return back()->with('error', 'Free mode is inactive. You cannot grant the free plan right now.');
        }

        $subscriptionService->grantFreeSubscription(
            $user->id,
            $request->plan_slug,
            $request->integer('duration_days'),
            auth()->id(),
        );

        $plan = $subscriptionService->getPlanBySlug($request->plan_slug);
        if ($plan) {
            $durationDays = $plan->slug === 'lifetime' ? null : $request->integer('duration_days');
            $freshUser = $user->fresh();

            app(LifecycleEmailService::class)->sendFreeSubscriptionGranted(
                $freshUser,
                $plan,
                $durationDays
            );

            if ($freshUser && $freshUser->hasTelegramLinked()) {
                $durationLabel = $plan->slug === 'lifetime'
                    ? 'Lifetime access'
                    : "{$durationDays} days";

                try {
                    AdminTelegramMessage::create([
                        'admin_id' => (int) auth()->id(),
                        'recipient_user_id' => $freshUser->id,
                        'recipient_chat_id' => $freshUser->telegram_chat_id,
                        'batch_id' => null,
                        'is_broadcast' => false,
                        'message' => "<b>Subscription Update</b>\n\n"
                            . "Your complimentary <b>{$plan->name}</b> plan has been granted by admin.\n"
                            . "Duration: <b>{$durationLabel}</b>\n\n"
                            . "Open your dashboard to continue.",
                        'image_path' => null,
                        'status' => 'pending',
                        'attempts' => 0,
                        'success' => false,
                        'error_message' => null,
                        'sent_at' => null,
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('simulator')->warning('Failed to queue admin grant Telegram message', [
                        'user_id' => $freshUser->id,
                        'plan' => $plan->slug,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $durationLabel = $request->plan_slug === 'lifetime'
            ? 'lifetime access'
            : "{$request->duration_days} days";

        return back()->with('success', "Complimentary {$request->plan_slug} subscription granted to {$user->name} ({$durationLabel}).");
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
