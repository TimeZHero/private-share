<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API responses carry ciphertext, metadata and one-time URLs: no browser or
 * proxy may keep a copy. Laravel's cache.headers middleware skips POST, so
 * this sets the header unconditionally.
 */
class PreventResponseCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
