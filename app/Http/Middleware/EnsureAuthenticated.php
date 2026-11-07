<?php

namespace App\Http\Middleware;

use App\Features\Authentication;
use App\Services\GuestAccess;
use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

/**
 * The guest grant was already checked against the database by
 * RestoreGuestAccess, so its presence in the session is enough here.
 */
class EnsureAuthenticated
{
    public function __construct(private GuestAccess $guestAccess) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Feature::active(Authentication::class)) {
            return $next($request);
        }

        if ($request->user()) {
            return $next($request);
        }

        if ($this->guestAccess->isGuest($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login');
    }
}
