<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            $statusCode = $response->getStatusCode();

            // Only error responses become styled pages. Redirects (e.g. auth) and
            // successful responses must pass through untouched.
            if ($statusCode < 400) {
                return $response;
            }

            // In production every client/server error renders the branded page so
            // nothing falls through to the default framework error page. Locally we
            // keep Laravel's debug page for unexpected errors (so stack traces stay
            // available) and only style the predictable user-facing codes.
            $localStyledCodes = [403, 404, 419, 429, 503];
            if (app()->environment('local') && ! in_array($statusCode, $localStyledCodes)) {
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

            // Only surface messages from intentional HTTP exceptions (e.g. abort(410, '...')).
            // Raw messages from unexpected exceptions could leak internal details.
            $message = $exception instanceof HttpExceptionInterface ? $exception->getMessage() : '';

            return Inertia::render('Error', [
                'status' => $statusCode,
                'message' => $message,
            ])
                ->toResponse($request)
                ->setStatusCode($statusCode);
        });
    })
    ->booted(function (Application $app) {
        if ($app->isLocal()) {
            TrustProxies::at('*');
        }
    })
    ->create();
