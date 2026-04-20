<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;

it('returns a successful response when unauthenticated and feature flag is off', function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => false, 'features.file_uploads' => false]);

    $this->get('/')->assertStatus(200);
});

it('returns a successful response when authenticated and feature flag is on', function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true, 'features.file_uploads' => true]);

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertStatus(200);
});

it('renders the Home Inertia page', function () {
    Feature::purge(Authentication::class);
    config(['features.auth' => false]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('fileUploadsEnabled')
            ->has('maxSizeGb'));
});
