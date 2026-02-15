<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Settings\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLiveTrading
{
    public function __construct(private PlatformSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->settings->getBool('FEATURE_LIVE_TRADING', false)) {
            abort(404);
        }

        return $next($request);
    }
}
