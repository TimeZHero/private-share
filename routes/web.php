<?php

use App\Http\Controllers\SecretController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/secrets', [SecretController::class, 'store'])->name('secrets.store');
Route::get('/secrets/{secret}/check', [SecretController::class, 'check'])->name('secrets.check');
Route::post('/secrets/{secret}/retrieve', [SecretController::class, 'retrieve'])->name('secrets.retrieve');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
