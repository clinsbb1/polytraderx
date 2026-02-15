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

        // Free users are always active
        if ($user->subscription_plan === 'free') {
            return $next($request);
        }

        // Lifetime users are always active
        if ($user->is_lifetime) {
            return $next($request);
        }

        // Paid users: check expiry
        if ($user->subscription_ends_at && $user->subscription_ends_at->isPast()) {
            // Expired — downgrade to free
            $this->subscriptionService->expire($user);

            return redirect()->route('subscription')
                ->with('warning', 'Your subscription has expired. You\'ve been moved to the Free plan.');
        }

        // Allow access to settings, subscription, and profile routes even with expired subscription
        $path = $request->path();
        if (str_starts_with($path, 'settings/') || str_starts_with($path, 'subscription') || str_starts_with($path, 'profile')) {
            return $next($request);
        }

        return $next($request);
    }
}
