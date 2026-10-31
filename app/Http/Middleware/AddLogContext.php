<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every log line written while serving the request carries who made it.
 * Runs after the session starts so guest sessions are visible.
 */
class AddLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        Context::add([
            'request_id' => (string) Str::uuid(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'guest_link_id' => $request->hasSession() ? $request->session()->get('guest_link_id') : null,
        ]);

        return $next($request);
    }
}
