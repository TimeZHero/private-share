<?php

use Illuminate\Support\Facades\Route;

it('renders 400 error page via 4xx handler', function () {
    Route::get('/test-400', fn () => abort(400));

    $response = $this->get('/test-400');

    $response->assertStatus(400);
    $response->assertSee('400');
    $response->assertSee('Bad Request');
});

it('renders 401 error page via 4xx handler', function () {
    Route::get('/test-401', fn () => abort(401));

    $response = $this->get('/test-401');

    $response->assertStatus(401);
    $response->assertSee('401');
    $response->assertSee('Unauthorized');
});

it('renders 404 error page', function () {
    $response = $this->get('/non-existent-page-that-does-not-exist');

    $response->assertStatus(404);
    $response->assertSee('404');
    $response->assertSee('Page Not Found');
    $response->assertSee('Looking for a secret?');
});

it('renders 403 error page via 4xx handler', function () {
    Route::get('/test-403', fn () => abort(403));

    $response = $this->get('/test-403');

    $response->assertStatus(403);
    $response->assertSee('403');
    $response->assertSee('Forbidden');
});

it('renders 419 error page via 4xx handler', function () {
    Route::get('/test-419', fn () => abort(419));

    $response = $this->get('/test-419');

    $response->assertStatus(419);
    $response->assertSee('419');
    $response->assertSee('Page Expired');
});

it('renders 429 error page via 4xx handler', function () {
    Route::get('/test-429', fn () => abort(429));

    $response = $this->get('/test-429');

    $response->assertStatus(429);
    $response->assertSee('429');
    $response->assertSee('Too Many Requests');
});

it('renders 500 error page', function () {
    Route::get('/test-500', fn () => abort(500));

    $response = $this->get('/test-500');

    $response->assertStatus(500);
    $response->assertSee('500');
    $response->assertSee('Server Error');
});

it('renders 502 error page via 5xx handler', function () {
    Route::get('/test-502', fn () => abort(502));

    $response = $this->get('/test-502');

    $response->assertStatus(502);
    $response->assertSee('502');
    $response->assertSee('Bad Gateway');
});

it('renders 503 error page', function () {
    Route::get('/test-503', fn () => abort(503));

    $response = $this->get('/test-503');

    $response->assertStatus(503);
    $response->assertSee('503');
    $response->assertSee('Under Maintenance');
});
