<?php

namespace App\Http\Controllers;

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\Secret;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function home(): InertiaResponse
    {
        $fileFeatureActive = Feature::active(FileUploads::class);
        $isAuthenticated = auth()->check();

        return Inertia::render('Home', [
            'fileUploadsEnabled' => $fileFeatureActive && $isAuthenticated,
            'maxSizeGb' => config('features.file_max_size_gb'),
        ]);
    }

    public function login(): InertiaResponse|\Illuminate\Http\RedirectResponse
    {
        if (! Feature::active(Authentication::class)) {
            abort(404);
        }

        if (auth()->check()) {
            return redirect()->route('home');
        }

        return Inertia::render('Login', [
            'error' => session('error'),
            'debugLoginUrl' => app()->isLocal() && config('app.debug')
                ? route('auth.debug-login')
                : null,
            'googleRedirectUrl' => route('auth.redirect', 'google'),
        ]);
    }

    public function show(Secret $secret): Response
    {
        return Inertia::render('Secret', [
            'secretId' => $secret->id,
            'createdAt' => $secret->created_at->format('M j, Y \a\t g:i A'),
        ])
            ->toResponse(request())
            ->setCache([
                'no_store' => true,
                'no_cache' => true,
                'must_revalidate' => true,
                'max_age' => 0,
            ]);
    }
}
