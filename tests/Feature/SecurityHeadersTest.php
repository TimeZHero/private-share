<?php

use App\Features\Authentication;
use App\Models\Secret;
use Illuminate\Support\Facades\Vite;
use Laravel\Pennant\Feature;

function cspDirective(string $policy, string $directive): array
{
    preg_match('/(?:^|; )'.preg_quote($directive, '/').' ([^;]+)/', $policy, $matches);

    return explode(' ', $matches[1] ?? '');
}

function connectSources(string $policy): array
{
    return cspDirective($policy, 'connect-src');
}

beforeEach(function () {
    config([
        'features.file_disk' => 'local',
        'app.csp_connect_sources' => [],
    ]);
});

test('pages forbid framing by other origins and MIME sniffing', function () {
    $this->get('/')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()')
        ->assertHeader('Content-Security-Policy');

    expect($this->get('/')->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'");
});

test('scripts may only connect to the app origin by default', function () {
    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect(connectSources($policy))->toBe(["'self'"]);
});

test('API and error responses carry the same headers', function () {
    $secret = Secret::factory()->create();

    $this->getJson("/api/secrets/{$secret->id}/check")
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Content-Security-Policy');

    $this->get('/AAAAAAAAAAAA')
        ->assertNotFound()
        ->assertHeader('Content-Security-Policy');
});

test('the S3 origin is allowed when files are stored on S3', function () {
    config([
        'features.file_disk' => 's3',
        'filesystems.disks.s3.region' => 'eu-west-1',
        'filesystems.disks.s3.bucket' => 'private-share-files',
        'filesystems.disks.s3.key' => 'test-key',
        'filesystems.disks.s3.secret' => 'test-secret',
    ]);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect(connectSources($policy))->toBe(["'self'", 'https://private-share-files.s3.eu-west-1.amazonaws.com']);
});

test('a custom S3 endpoint is allowed instead of AWS', function () {
    config([
        'features.file_disk' => 's3',
        'filesystems.disks.s3.region' => 'us-east-1',
        'filesystems.disks.s3.bucket' => 'files',
        'filesystems.disks.s3.key' => 'test-key',
        'filesystems.disks.s3.secret' => 'test-secret',
        'filesystems.disks.s3.endpoint' => 'https://minio.example.org:9000',
        'filesystems.disks.s3.use_path_style_endpoint' => true,
    ]);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect(connectSources($policy))->toBe(["'self'", 'https://minio.example.org:9000']);
});

test('extra origins can be allowed through configuration', function () {
    config(['app.csp_connect_sources' => ['https://uploads.example.org', 'https://cdn.example.org']]);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect(connectSources($policy))->toBe(["'self'", 'https://uploads.example.org', 'https://cdn.example.org']);
});

test('the Vite dev server is allowed only while it is running', function () {
    $hotFile = storage_path('framework/testing/hot');
    file_put_contents($hotFile, 'http://localhost:5173');
    Vite::useHotFile($hotFile);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    unlink($hotFile);

    expect(connectSources($policy))->toBe(["'self'", 'http://localhost:5173', 'ws://localhost:5173']);
});

test('session cookies are Secure outside local development', function () {
    $sessionCookie = collect($this->get('/')->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

    expect($sessionCookie->isSecure())->toBeTrue()
        ->and($sessionCookie->isHttpOnly())->toBeTrue();
});

describe('full content security policy', function () {
    test('scripts only run from the app or with the per-request nonce', function () {
        $response = $this->get('/');
        $policy = $response->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", implode(' ', cspDirective($policy, 'script-src')), $nonce);

        expect(cspDirective($policy, 'script-src'))->toBe(["'self'", "'nonce-{$nonce[1]}'"])
            ->and($response->getContent())->toContain("nonce=\"{$nonce[1]}\"");
    });

    test('every request gets a different nonce', function () {
        $first = cspDirective($this->get('/')->headers->get('Content-Security-Policy'), 'script-src');
        $second = cspDirective($this->get('/')->headers->get('Content-Security-Policy'), 'script-src');

        expect($first)->not->toBe($second);
    });

    test('images, fonts and styles are limited to known sources', function () {
        $policy = $this->get('/')->headers->get('Content-Security-Policy');

        expect(cspDirective($policy, 'default-src'))->toBe(["'self'"])
            ->and(cspDirective($policy, 'img-src'))->toBe(["'self'", 'data:', 'blob:'])
            ->and(cspDirective($policy, 'font-src'))->toBe(["'self'", 'https://fonts.bunny.net', 'https://cdn.jsdelivr.net'])
            ->and(cspDirective($policy, 'style-src'))->toBe(["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'])
            ->and(cspDirective($policy, 'object-src'))->toBe(["'none'"])
            ->and(cspDirective($policy, 'base-uri'))->toBe(["'self'"])
            ->and(cspDirective($policy, 'form-action'))->toBe(["'self'"]);
    });

    test('Google avatars are allowed only when sign-in is enabled', function () {
        Feature::purge(Authentication::class);
        config(['features.auth' => true]);

        $policy = $this->get('/login')->headers->get('Content-Security-Policy');

        expect(cspDirective($policy, 'img-src'))->toContain('https://*.googleusercontent.com');
    });

    test('the Vite dev server may serve scripts and styles while it runs', function () {
        $hotFile = storage_path('framework/testing/hot');
        file_put_contents($hotFile, 'http://localhost:5173');
        Vite::useHotFile($hotFile);

        $policy = $this->get('/')->headers->get('Content-Security-Policy');

        unlink($hotFile);

        expect(cspDirective($policy, 'script-src'))->toContain('http://localhost:5173')
            ->and(cspDirective($policy, 'style-src'))->toContain('http://localhost:5173');
    });
});
