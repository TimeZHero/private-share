<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            $inertiaErrorCodes = [400, 401, 403, 404, 405, 408, 419, 422, 429, 500, 501, 502, 503, 504];
            $statusCode = $response->getStatusCode();

            $shouldRenderInertia = (! app()->environment('local') && in_array($statusCode, $inertiaErrorCodes))
                || (app()->environment('local') && in_array($statusCode, [404, 403, 419, 429, 503]));

            if (! $shouldRenderInertia) {
                return $response;
            }

            $user = $request->user();
            $guestExpiresAt = $request->hasSession() ? $request->session()->get('guest_link_expires_at') : null;

            Inertia::share([
                'auth' => [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ] : null,
                    'guest' => $guestExpiresAt ? ['expiresAt' => $guestExpiresAt] : null,
                ],
                'branding' => [
                    'primary' => config('branding.primary'),
                    'secondary' => config('branding.secondary'),
                    'accent' => config('branding.accent'),
                    'action' => config('branding.action'),
                    'background' => config('branding.background'),
                    'foreground' => config('branding.foreground'),
                    'logo' => config('branding.logo'),
                    'logoSize' => config('branding.logo_size'),
                ],
                'features' => [
                    'auth' => Feature::active(Authentication::class),
                    'fileUploads' => Feature::active(FileUploads::class),
                ],
                'appName' => config('app.name'),
                'debug' => app()->isLocal() && config('app.debug'),
                'flash' => ['error' => null, 'success' => null],
            ]);

            return Inertia::render('Error', [
                'status' => $statusCode,
                'message' => $exception->getMessage(),
            ])
                ->toResponse($request)
                ->setStatusCode($statusCode);
        });
    })->create();
