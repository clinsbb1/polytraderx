<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Only update if last login is older than 5 minutes (to avoid excessive DB writes)
            if (!$user->last_login_at || $user->last_login_at->lt(now()->subMinutes(5))) {
                $user->update(['last_login_at' => now()]);
            }
        }

        return $next($request);
    }
}
