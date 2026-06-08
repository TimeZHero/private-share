<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('renders 400 error page via Inertia', function () {
    Route::get('/test-400', fn () => abort(400));

    $this->get('/test-400')
        ->assertStatus(400)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 400));
});

it('renders 401 error page via Inertia', function () {
    Route::get('/test-401', fn () => abort(401));

    $this->get('/test-401')
        ->assertStatus(401)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 401));
});

it('renders 404 error page', function () {
    $this->get('/non-existent-page-that-does-not-exist')
        ->assertStatus(404)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 404));
});

it('renders 403 error page via Inertia', function () {
    Route::get('/test-403', fn () => abort(403));

    $this->get('/test-403')
        ->assertStatus(403)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 403));
});

it('renders 419 error page via Inertia', function () {
    Route::get('/test-419', fn () => abort(419));

    $this->get('/test-419')
        ->assertStatus(419)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 419));
});

it('renders 429 error page via Inertia', function () {
    Route::get('/test-429', fn () => abort(429));

    $this->get('/test-429')
        ->assertStatus(429)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 429));
});

it('renders 500 error page', function () {
    Route::get('/test-500', fn () => abort(500));

    $this->get('/test-500')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 500));
});

it('renders 502 error page via Inertia', function () {
    Route::get('/test-502', fn () => abort(502));

    $this->get('/test-502')
        ->assertStatus(502)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 502));
});

it('renders 503 error page', function () {
    Route::get('/test-503', fn () => abort(503));

    $this->get('/test-503')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 503));
});

it('renders 410 error page via Inertia', function () {
    Route::get('/test-410', fn () => abort(410));

    $this->get('/test-410')
        ->assertStatus(410)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 410));
});

it('renders any unlisted error code via Inertia so nothing falls through', function () {
    Route::get('/test-418', fn () => abort(418));

    $this->get('/test-418')
        ->assertStatus(418)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 418));
});

it('surfaces intentional abort messages', function () {
    Route::get('/test-message', fn () => abort(410, 'This link has expired.'));

    $this->get('/test-message')
        ->assertStatus(410)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('message', 'This link has expired.'));
});

it('does not surface raw messages from unexpected exceptions', function () {
    Route::get('/test-leak', function () {
        throw new RuntimeException('Sensitive internal detail');
    });

    $this->get('/test-leak')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 500)
            ->where('message', ''));
});

it('returns JSON for API error responses instead of the Inertia page', function () {
    Route::get('/test-json', fn () => abort(410, 'Gone via json'));

    $this->getJson('/test-json')
        ->assertStatus(410)
        ->assertJson(['message' => 'Gone via json']);
});

it('does not turn redirect responses into error pages', function () {
    Route::get('/test-redirect', fn () => redirect('/somewhere'));

    $this->get('/test-redirect')
        ->assertRedirect('/somewhere');
});
