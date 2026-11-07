<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\User;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true, 'features.file_uploads' => true]);
});

test('every application route is rate limited', function () {
    $exempt = [
        'up',                          // health check polled by monitoring
        'storage/{path}',              // framework local-disk route, signature-protected
        'sanctum/csrf-cookie',
        '_inertia/devtools/entries',   // local only
        '_inertia/devtools/entries/{id}',
    ];

    $unthrottled = collect(Route::getRoutes()->getRoutes())
        ->reject(fn (RegisteredRoute $route) => in_array($route->uri(), $exempt, true))
        ->reject(fn (RegisteredRoute $route) => collect($route->gatherMiddleware())
            ->contains(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'throttle:')))
        ->map(fn (RegisteredRoute $route) => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($unthrottled)->toBe([]);
});

test('throttled API responses say how long to wait', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->postJson('/api/guest-links', ['duration_days' => 1])->assertSuccessful();
    }

    $this->actingAs($user)->postJson('/api/guest-links', ['duration_days' => 1])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJson([
            'error' => 'rate_limited',
            'message' => 'Too many requests. Try again in 1 minute.',
        ]);
});

test('upload chunks allow sustained gigabit uploads', function () {
    $user = User::factory()->create();

    foreach (range(1, 1000) as $chunk) {
        $this->actingAs($user)->postJson('/api/files/missing-upload/chunk')->assertNotFound();
    }

    $this->actingAs($user)->postJson('/api/files/missing-upload/chunk')->assertTooManyRequests();
});

test('limits for signed-in users follow the user, not the IP', function () {
    $sharedOfficeIp = ['REMOTE_ADDR' => '93.184.216.34'];
    $firstUser = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($firstUser)->withServerVariables($sharedOfficeIp)->postJson('/api/guest-links', ['duration_days' => 1]);
    }

    $this->actingAs(User::factory()->create())->withServerVariables($sharedOfficeIp)
        ->postJson('/api/guest-links', ['duration_days' => 1])
        ->assertSuccessful();
});

test('file downloads are limited per client', function () {
    foreach (range(1, 30) as $attempt) {
        $this->postJson('/api/files/missing-file/download')->assertNotFound();
    }

    $this->postJson('/api/files/missing-file/download')->assertTooManyRequests();
});

test('Google sign-in is limited per client', function () {
    foreach (range(1, 10) as $attempt) {
        $this->get('/auth/google/redirect');
    }

    $this->get('/auth/google/redirect')->assertTooManyRequests();
});
