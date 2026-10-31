<?php

use App\Enums\RateLimit;
use App\Features\Authentication;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuestSessionController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\EnsureAuthenticated;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::get('/', [PageController::class, 'home'])
    ->middleware([RateLimit::Pages->middleware(), EnsureAuthenticated::class])
    ->name('home');

Route::get('/{secret}', [PageController::class, 'show'])
    ->where('secret', '[A-Za-z0-9]{12}')
    ->middleware(RateLimit::SecretViewing->middleware())
    ->name('secret.show');

Route::middleware(EnsureFeaturesAreActive::using(Authentication::class))->group(function () {
    Route::get('/login', [PageController::class, 'login'])
        ->middleware(RateLimit::Pages->middleware())
        ->name('login');

    Route::middleware(RateLimit::SignIn->middleware())->group(function () {
        Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])
            ->where('provider', 'google')
            ->name('auth.redirect');

        Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])
            ->where('provider', 'google')
            ->name('auth.callback');
    });

    Route::middleware([RateLimit::GuestAccess->middleware(), 'signed'])->group(function () {
        Route::get('/guest/{guestLink}', [GuestSessionController::class, 'create'])
            ->name('guest.access');

        Route::post('/guest/{guestLink}', [GuestSessionController::class, 'store'])
            ->name('guest.accept');
    });
});

Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware(RateLimit::Pages->middleware())
    ->name('auth.logout');

if (app()->isLocal() && config('app.debug')) {
    Route::get('/auth/debug-login', [AuthController::class, 'debugLogin'])
        ->middleware(RateLimit::Pages->middleware())
        ->name('auth.debug-login');
}
