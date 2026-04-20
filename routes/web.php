<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuestLinkController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\EnsureAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])
    ->middleware(EnsureAuthenticated::class)
    ->name('home');

Route::get('/login', [PageController::class, 'login'])
    ->name('login');

Route::get('/{secret}', [PageController::class, 'show'])
    ->where('secret', '[A-Za-z0-9]{12}')
    ->middleware('throttle:secrets')
    ->name('secret.show');

Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])
    ->where('provider', 'google')
    ->name('auth.redirect');

Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])
    ->where('provider', 'google')
    ->name('auth.callback');

Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->name('auth.logout');

Route::get('/guest/{guestLink}', [GuestLinkController::class, 'access'])
    ->middleware('signed')
    ->name('guest.access');

if (app()->isLocal() && config('app.debug')) {
    Route::get('/auth/debug-login', [AuthController::class, 'debugLogin'])
        ->name('auth.debug-login');
}
