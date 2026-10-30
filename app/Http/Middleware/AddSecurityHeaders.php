<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $connectSources = implode(' ', array_unique([
            "'self'",
            ...array_filter([$this->fileStorageOrigin()]),
            ...config('app.csp_connect_sources', []),
            ...$this->viteDevServerOrigins(),
        ]));

        return "connect-src {$connectSources}; frame-ancestors 'self'";
    }

    /**
     * Encrypted files are downloaded straight from the bucket through a signed URL.
     */
    private function fileStorageOrigin(): ?string
    {
        $disk = Storage::disk(config('features.file_disk'));

        if (! $disk instanceof AwsS3V3Adapter) {
            return null;
        }

        $config = $disk->getConfig();
        $signedUrlBase = $config['temporary_url'] ?? $disk->getClient()->getObjectUrl($config['bucket'], 'probe');

        return $this->origin($signedUrlBase);
    }

    /** @return list<string> */
    private function viteDevServerOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return [];
        }

        $httpOrigin = $this->origin(trim(file_get_contents(Vite::hotFile())));

        return [$httpOrigin, preg_replace('/^http/', 'ws', $httpOrigin)];
    }

    private function origin(string $url): string
    {
        $parts = parse_url($url);
        $port = isset($parts['port']) ? ":{$parts['port']}" : '';

        return "{$parts['scheme']}://{$parts['host']}{$port}";
    }
}
