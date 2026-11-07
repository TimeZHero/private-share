<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\GuestLink;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true, 'features.file_uploads' => true]);
});

describe('Guest Link Creation (API)', function () {
    it('requires authentication to create a guest link', function () {
        $response = $this->postJson('/api/guest-links');

        $response->assertUnauthorized();
    });

    it('lasts the chosen number of days from creation', function (int $days, string $expectedExpiry) {
        $this->travelTo('2026-11-07 10:00:00');

        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/guest-links', ['duration_days' => $days]);

        $response->assertSuccessful()
            ->assertJsonPath('duration_days', $days)
            ->assertJsonPath('expires_at', $expectedExpiry);
    })->with([
        '1 day' => [1, '2026-11-08T10:00:00+00:00'],
        '7 days' => [7, '2026-11-14T10:00:00+00:00'],
        '14 days' => [14, '2026-11-21T10:00:00+00:00'],
    ]);

    it('rejects durations other than 1, 7 or 14 days', function (mixed $days) {
        $payload = $days === null ? [] : ['duration_days' => $days];

        $this->actingAs(User::factory()->create())
            ->postJson('/api/guest-links', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('duration_days');

        expect(GuestLink::count())->toBe(0);
    })->with([
        'missing' => [null],
        'zero' => [0],
        'three' => [3],
        'a year' => [365],
        'text' => ['seven'],
    ]);

    it('does not let a guest invite further guests', function () {
        $guestLink = GuestLink::factory()->create();

        $this->withSession([
            'guest_link_id' => $guestLink->id,
            'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
        ])->postJson('/api/guest-links', ['duration_days' => 1], ['Referer' => 'http://localhost/'])
            ->assertUnauthorized();
    });

    it('returns a signed URL that points to the guest access route', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/guest-links', ['duration_days' => 1]);

        $url = $response->json('url');
        expect($url)->toContain('/guest/');
        expect($url)->toContain('signature=');
    });
});

describe('Guest Link Access', function () {
    it('shows a confirmation page without using up the link', function () {
        $guestLink = GuestLink::factory()->create();

        $this->get(URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GuestAccess')
                ->has('acceptUrl')
                ->where('expiresAt', $guestLink->expires_at->toIso8601String()));

        expect($guestLink->fresh()->used_at)->toBeNull()
            ->and(session('guest_link_id'))->toBeNull();
    });

    it('starts a guest session when the guest continues', function () {
        $guestLink = GuestLink::factory()->create();

        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]))
            ->assertRedirect(route('home'));

        expect(session('guest_link_id'))->toBe($guestLink->id)
            ->and(session('guest_link_expires_at'))->toBe($guestLink->expires_at->toIso8601String())
            ->and($guestLink->fresh()->used_at)->not->toBeNull();
    });

    it('issues a fresh session id when the guest continues', function () {
        $guestLink = GuestLink::factory()->create();
        $this->startSession();
        $sessionIdBefore = session()->getId();

        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]));

        expect(session()->getId())->not->toBe($sessionIdBefore);
    });

    it('works only once', function () {
        $guestLink = GuestLink::factory()->create();
        $acceptUrl = URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]);
        $this->post($acceptUrl)->assertRedirect(route('home'));
        session()->flush();

        $this->post($acceptUrl)->assertStatus(410);
        $this->get(URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]))
            ->assertStatus(410)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('message', 'This guest link has already been used.'));
    });

    it('sends the guest who already used the link straight home', function () {
        $guestLink = GuestLink::factory()->create();
        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]));

        $this->get(URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]))
            ->assertRedirect(route('home'));
    });

    it('rejects access with invalid signature', function () {
        $guestLink = GuestLink::factory()->create();

        $this->get('/guest/'.$guestLink->id.'?signature=invalid')->assertForbidden();
        $this->post('/guest/'.$guestLink->id.'?signature=invalid')->assertForbidden();

        expect($guestLink->fresh()->used_at)->toBeNull();
    });

    it('returns 410 for expired guest link and renders the branded error page', function () {
        $guestLink = GuestLink::factory()->expired()->create();

        $this->get(URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]))
            ->assertStatus(410)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 410)
                ->where('message', 'This guest link has expired.'));

        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]))->assertStatus(410);
    });

    it('returns 404 for non-existent guest link', function () {
        $this->get(URL::signedRoute('guest.access', ['guestLink' => 'nonexistent']))->assertNotFound();
    });
});

describe('Guest Session on Home Page', function () {
    it('allows unauthenticated user with valid guest session to access home', function () {
        $guestLink = GuestLink::factory()->create();

        $response = $this->withSession([
            'guest_link_id' => $guestLink->id,
            'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
        ])->get('/');

        $response->assertSuccessful();
    });

    it('redirects to login when guest session is expired', function () {
        $guestLink = GuestLink::factory()->expired()->create();

        $response = $this->withSession([
            'guest_link_id' => $guestLink->id,
            'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
        ])->get('/');

        $response->assertRedirect(route('login'));
    });

    it('redirects to login when no guest session and not authenticated', function () {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    });

    it('guest user can create secrets via API', function () {
        $guestLink = GuestLink::factory()->create();

        $response = $this->withSession([
            'guest_link_id' => $guestLink->id,
            'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
        ])->postJson('/api/secrets', [
            'content' => 'encrypted-guest-secret',
        ], ['Referer' => 'http://localhost/']);

        $response->assertSuccessful();
        $response->assertJsonStructure(['id']);
    });
});

describe('Guest Link Model', function () {
    it('generates a 32-character string ID', function () {
        $guestLink = GuestLink::factory()->create();

        expect($guestLink->id)->toBeString()->toHaveLength(32);
    });

    it('belongs to a user', function () {
        $guestLink = GuestLink::factory()->create();

        expect($guestLink->creator)->toBeInstanceOf(User::class);
    });

    it('knows if it is expired', function () {
        $active = GuestLink::factory()->create();
        $expired = GuestLink::factory()->expired()->create();

        expect($active->isExpired())->toBeFalse();
        expect($expired->isExpired())->toBeTrue();
    });

    it('active scope returns only non-expired links', function () {
        GuestLink::factory()->create();
        GuestLink::factory()->expired()->create();

        expect(GuestLink::active()->count())->toBe(1);
    });
});
