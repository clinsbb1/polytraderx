<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSimulationAcknowledged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Skip check if already on the acknowledgment page
        if ($request->routeIs('simulation.acknowledge') || $request->routeIs('simulation.accept')) {
            return $next($request);
        }

        // Check if user has acknowledged
        if (!$user->simulation_acknowledged_at) {
            return redirect()->route('simulation.acknowledge');
        }

        return $next($request);
    }
}
