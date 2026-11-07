<?php

namespace App\Http\Middleware;

use App\Services\GuestAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs right after AddLogContext (so its own log lines carry the request id)
 * and before anything else reads the guest grant: rate limit keys, upload
 * ownership, shared props, EnsureAuthenticated. GuestAccess keeps the
 * guest_link_id log context in sync when it grants or ends access.
 */
class RestoreGuestAccess
{
    public function __construct(private GuestAccess $guestAccess) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->guestAccess->resolve($request);

        return $next($request);
    }
}
