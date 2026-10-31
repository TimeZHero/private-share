<?php

namespace App\Http\Middleware;

use App\Features\Authentication;
use Closure;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

/**
 * The CSP's job is to keep injected code from running or from sending the
 * decryption key anywhere: scripts need the per-request nonce, and every
 * channel that can carry data out (fetch, images, fonts, forms) is pinned to
 * known origins. Styles allow 'unsafe-inline' because sonner injects its CSS
 * at runtime without nonce support; CSS can only leak through url() loads,
 * which img-src and font-src already restrict.
 */
class AddSecurityHeaders
{
    /** Must match the stylesheet in resources/views/app.blade.php. */
    private const string FONT_ORIGIN = 'https://fonts.bunny.net';

    private const string GOOGLE_AVATAR_ORIGIN = 'https://*.googleusercontent.com';

    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Secret URLs carry the secret id: never send them to other sites.
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $devServer = $this->viteDevServerOrigins();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-".Vite::cspNonce()."'", ...array_slice($devServer, 0, 1)],
            'style-src' => ["'self'", "'unsafe-inline'", self::FONT_ORIGIN, ...array_slice($devServer, 0, 1)],
            'font-src' => ["'self'", self::FONT_ORIGIN],
            'img-src' => ["'self'", 'data:', 'blob:', ...$this->avatarOrigins()],
            'connect-src' => [
                "'self'",
                ...array_filter([$this->fileStorageOrigin()]),
                ...config('app.csp_connect_sources', []),
                ...$devServer,
            ],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', array_unique($sources)))
            ->implode('; ');
    }

    /** @return list<string> */
    private function avatarOrigins(): array
    {
        return Feature::active(Authentication::class) ? [self::GOOGLE_AVATAR_ORIGIN] : [];
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

    /**
     * @return list<string> the dev server's http origin, then its ws origin (HMR)
     */
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
