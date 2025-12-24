<?php

use App\Http\Controllers\SecretController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:secrets'])->group(function () {
    Route::post('/secrets', [SecretController::class, 'store'])->name('secrets.store');
    Route::get('/secrets/{secret}/check', [SecretController::class, 'check'])->name('secrets.check');
});

Route::middleware(['throttle:secret-password'])->group(function () {
    Route::post('/secrets/{secret}/retrieve', [SecretController::class, 'retrieve'])->name('secrets.retrieve');
});
