<?php

use App\Features\Authentication;
use App\Models\GuestLink;
use App\Models\Secret;
use App\Models\User;
use Illuminate\Support\Facades\URL;
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

    it('lets a signed-in user share a secret', function () {
        $this->actingAs(User::factory()->create());

        visit('/')
            ->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->fill('#content', 'Shared by a signed-in user')
            ->click('Share Secret')
            ->waitForText('Secret Created!');

        expect(Secret::count())->toBe(1);
    });

    it('lets a guest share a secret through a guest link', function () {
        // The browser test server runs on a random 127.0.0.1 port that Sanctum does not treat as first-party.
        config(['sanctum.stateful' => ['127.0.0.1:*']]);
        $guestLink = GuestLink::factory()->create();
        $page = visit('/login');

        // Sign against the browser test server's host, which differs from APP_URL.
        URL::forceRootUrl(rtrim(str_replace(parse_url($page->url(), PHP_URL_PATH), '', $page->url()), '/'));
        $guestUrl = URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]);
        URL::forceRootUrl(null);

        $page->navigate($guestUrl)
            ->waitForText('Continue as guest')
            ->click('Continue as guest')
            ->waitForText('Sharing a secret')
            ->click('[aria-label="Close"]')
            ->fill('#content', 'Shared by a guest')
            ->click('Share Secret')
            ->waitForText('Secret Created!');

        expect(Secret::count())->toBe(1);
    });
});
