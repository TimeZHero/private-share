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

    it('creates a guest link with default 24h TTL', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/guest-links');

        $response->assertSuccessful()
            ->assertJsonStructure(['id', 'url', 'expires_at', 'ttl_hours']);

        expect($response->json('ttl_hours'))->toBe(24);

        $this->assertDatabaseHas('guest_links', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
        ]);
    });

    it('returns a signed URL that points to the guest access route', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/guest-links');

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
