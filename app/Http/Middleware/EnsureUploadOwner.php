<?php

namespace App\Http\Middleware;

use App\Support\Uploader;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only whoever started an upload may add chunks to it or complete it.
 * Answers 404 so others cannot even confirm the upload exists.
 */
class EnsureUploadOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Uploader::from($request)->owns($request->route('pendingUpload')), 404);

        return $next($request);
    }
}
