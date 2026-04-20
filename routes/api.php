<?php

use App\Http\Controllers\GuestLinkController;
use App\Http\Controllers\SecretController;
use App\Http\Controllers\SharedFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:secrets'])->group(function () {
    Route::post('/secrets', [SecretController::class, 'store'])->name('secrets.store');
    Route::get('/secrets/{secret}/check', [SecretController::class, 'check'])->name('secrets.check');
});

Route::middleware(['throttle:secret-password'])->group(function () {
    Route::post('/secrets/{secret}/retrieve', [SecretController::class, 'retrieve'])->name('secrets.retrieve');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/files/initiate', [SharedFileController::class, 'initiate'])->name('files.initiate');
    Route::post('/files/{pendingUpload}/chunk', [SharedFileController::class, 'chunk'])->name('files.chunk');
    Route::post('/files/{pendingUpload}/complete', [SharedFileController::class, 'complete'])->name('files.complete');

    Route::post('/guest-links', [GuestLinkController::class, 'store'])->name('guest-links.store');
});

Route::post('/files/{sharedFile}/download', [SharedFileController::class, 'download'])->name('files.download');
