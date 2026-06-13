<?php

use App\Features\Authentication;
use App\Models\User;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge([Authentication::class]);
    config(['features.auth' => true]);
});

describe('Guest Link E2E', function () {
    it('shows a toast with the link duration when a guest link is created', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        $page = visit('/');

        $page->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->assertSee('Create Guest Link')
            ->click('Create Guest Link')
            ->waitForText('Lasts 24 hours')
            ->assertSee('Guest link')
            ->assertSee('Lasts 24 hours');
    });
});
