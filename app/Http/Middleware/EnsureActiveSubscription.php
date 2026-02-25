<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Subscription\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(private SubscriptionService $subscriptionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Allow these sections even when subscription is inactive/expired.
        $path = $request->path();
        $canAccessWithoutSubscription = str_starts_with($path, 'settings/')
            || str_starts_with($path, 'subscription')
            || str_starts_with($path, 'profile');

        if (!$user->is_active) {
            if ($canAccessWithoutSubscription) {
                return $next($request);
            }

            return redirect()->route('subscription')
                ->with('warning', 'Your account is inactive. Please activate a subscription to continue.');
        }

        // Lifetime users are always active
        if ($user->is_lifetime || $user->subscription_plan === 'lifetime') {
            return $next($request);
        }

        if ($user->subscription_plan === 'free') {
            if (!$this->subscriptionService->isFreeModeEnabled()) {
                $user->update([
                    'is_active' => false,
                    'billing_interval' => null,
                    'trial_ends_at' => null,
                    'subscription_ends_at' => null,
                ]);

                if ($canAccessWithoutSubscription) {
                    return $next($request);
                }

                return redirect()->route('subscription')
                    ->with('warning', 'Please select a plan to activate your account and get started.');
            }

            if ($user->trial_ends_at === null || $user->trial_ends_at->isFuture()) {
                return $next($request);
            }

            $user->update(['is_active' => false]);

            if ($canAccessWithoutSubscription) {
                return $next($request);
            }

            return redirect()->route('subscription')
                ->with('warning', 'Your free access has ended. Please choose a paid plan to continue.');
        }

        // Paid users: check expiry.
        if ($user->subscription_ends_at && $user->subscription_ends_at->isPast()) {
            $freeModeEnabled = $this->subscriptionService->isFreeModeEnabled();
            $this->subscriptionService->expire($user);

            return redirect()->route('subscription')
                ->with('warning', $freeModeEnabled
                    ? 'Your subscription has expired. You\'ve been moved to the Free plan.'
                    : 'Your subscription has expired. Please renew to continue.');
        }

        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            return $next($request);
        }

        if ($canAccessWithoutSubscription) {
            return $next($request);
        }

        return redirect()->route('subscription')
            ->with('warning', 'An active subscription is required to access this page.');
    }
}
