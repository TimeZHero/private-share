<?php

namespace App\Providers;

use App\Enums\RateLimit;
use Illuminate\Support\Facades\RateLimiter;
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
    }
}
