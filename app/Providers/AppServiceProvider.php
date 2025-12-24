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

        // Stricter rate limiting for password attempts on protected secrets
        RateLimiter::for('secret-password', function (Request $request) {
            // Use the secret ID + IP combination to limit per-secret attempts
            $secretId = $request->route('secret')?->id ?? $request->route('secret') ?? 'unknown';

            return [
                // 5 attempts per minute per secret per IP
                Limit::perMinute(5)->by($request->ip().':'.$secretId)->response(function () {
                    return response()->json([
                        'error' => 'rate_limited',
                        'message' => 'Too many password attempts. Please wait before trying again.',
                    ], 429);
                }),
                // 20 total attempts per minute per IP (across all secrets)
                Limit::perMinute(20)->by($request->ip())->response(function () {
                    return response()->json([
                        'error' => 'rate_limited',
                        'message' => 'Too many requests. Please wait before trying again.',
                    ], 429);
                }),
            ];
        });
    }
}
