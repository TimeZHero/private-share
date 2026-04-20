<?php

namespace App\Http\Middleware;

use App\Features\Authentication;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Feature::active(Authentication::class)) {
            return $next($request);
        }

        if ($request->user()) {
            return $next($request);
        }

        if ($this->hasValidGuestSession($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login');
    }

    private function hasValidGuestSession(Request $request): bool
    {
        $guestLinkId = $request->session()->get('guest_link_id');
        $expiresAt = $request->session()->get('guest_link_expires_at');

        if (! $guestLinkId || ! $expiresAt) {
            return false;
        }

        if (Carbon::parse($expiresAt)->isPast()) {
            $request->session()->forget(['guest_link_id', 'guest_link_expires_at']);

            return false;
        }

        return true;
    }
}
