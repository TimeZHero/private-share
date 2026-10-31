<?php

namespace App\Providers;

use App\Enums\RateLimit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (RateLimit::cases() as $rateLimit) {
            RateLimiter::for($rateLimit->value, $rateLimit->limitFor(...));
        }

        EnsureFeaturesAreActive::whenInactive(fn () => abort(404));

        // Deploys briefly run old and new containers side by side; the lock lives in the shared cache store.
        Schedule::alwaysOnOneServer();
    }
}
