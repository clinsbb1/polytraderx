<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\SubscriptionPlan;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(PlatformSettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('*', function ($view): void {
            $view->with('freeModeEnabled', SubscriptionPlan::isFreeModeEnabled());
        });

        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
