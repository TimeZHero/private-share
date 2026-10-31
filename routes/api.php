<?php

use App\Enums\RateLimit;
use App\Features\Authentication;
use App\Features\FileUploads;
use App\Http\Controllers\GuestLinkController;
use App\Http\Controllers\SecretController;
use App\Http\Controllers\SharedFileController;
use App\Http\Middleware\EnsureAuthenticated;
use App\Http\Middleware\EnsureUploadOwner;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::post('/secrets', [SecretController::class, 'store'])
    ->middleware([RateLimit::SecretCreation->middleware(), EnsureAuthenticated::class])
    ->name('secrets.store');

Route::get('/secrets/{secret}/check', [SecretController::class, 'check'])
    ->middleware(RateLimit::SecretViewing->middleware())
    ->name('secrets.check');

Route::post('/secrets/{secret}/retrieve', [SecretController::class, 'retrieve'])
    ->middleware(RateLimit::SecretRetrieval->middleware())
    ->name('secrets.retrieve');

// Only reachable through the signed URL handed out when the secret is retrieved.
Route::post('/files/{sharedFile}/download', [SharedFileController::class, 'download'])
    ->middleware([RateLimit::FileDownload->middleware(), 'signed:relative'])
    ->name('files.download');

// Signed-in users and guest sessions can upload.
Route::middleware([
    EnsureFeaturesAreActive::using(Authentication::class),
    EnsureFeaturesAreActive::using(FileUploads::class),
    EnsureAuthenticated::class,
])->group(function () {
    Route::post('/files/initiate', [SharedFileController::class, 'initiate'])
        ->middleware(RateLimit::UploadSession->middleware())
        ->name('files.initiate');

    Route::post('/files/{pendingUpload}/chunk', [SharedFileController::class, 'chunk'])
        ->middleware([RateLimit::UploadChunk->middleware(), EnsureUploadOwner::class])
        ->name('files.chunk');

    Route::post('/files/{pendingUpload}/complete', [SharedFileController::class, 'complete'])
        ->middleware([RateLimit::UploadSession->middleware(), EnsureUploadOwner::class])
        ->name('files.complete');
});

// Only signed-in users may invite guests; a guest must not mint further access.
Route::post('/guest-links', [GuestLinkController::class, 'store'])
    ->middleware([EnsureFeaturesAreActive::using(Authentication::class), 'auth:sanctum', RateLimit::GuestLinkCreation->middleware()])
    ->name('guest-links.store');
