<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        if ($user->isSubscriptionActive()) {
            return $next($request);
        }

        // Allow access to settings, subscription, and profile routes even with expired subscription
        $path = $request->path();
        if (str_starts_with($path, 'settings/') || str_starts_with($path, 'subscription') || str_starts_with($path, 'profile')) {
            return $next($request);
        }

        return redirect('/subscription');
    }
}
