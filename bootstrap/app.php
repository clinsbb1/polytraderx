<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'subscribed' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'live_trading' => \App\Http\Middleware\RequireLiveTrading::class,
            'simulation_acknowledged' => \App\Http\Middleware\EnsureSimulationAcknowledged::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\TrackLastLogin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/webhooks/*',
        ]);

        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
