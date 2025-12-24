<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        // This helps prevent brute-force enumeration of secret IDs
        RateLimiter::for('secrets', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
