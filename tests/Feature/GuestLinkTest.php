<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\GuestLink;
use App\Models\User;
use Illuminate\Support\Facades\URL;
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
            ->assertJsonStructure(['id', 'url', 'expires_at']);

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
    it('redirects to home when accessing a valid signed guest link', function () {
        $guestLink = GuestLink::factory()->create();

        $signedUrl = URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]);

        $response = $this->get($signedUrl);

        $response->assertRedirect(route('home'));
    });

    it('sets guest session data on valid access', function () {
        $guestLink = GuestLink::factory()->create();

        $signedUrl = URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]);

        $this->get($signedUrl);

        expect(session('guest_link_id'))->toBe($guestLink->id);
        expect(session('guest_link_expires_at'))->toBe($guestLink->expires_at->toIso8601String());
    });

    it('rejects access with invalid signature', function () {
        $guestLink = GuestLink::factory()->create();

        $response = $this->get('/guest/'.$guestLink->id.'?signature=invalid');

        $response->assertForbidden();
    });

    it('returns 410 for expired guest link', function () {
        $guestLink = GuestLink::factory()->expired()->create();

        $signedUrl = URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]);

        $response = $this->get($signedUrl);

        $response->assertStatus(410);
    });

    it('returns 404 for non-existent guest link', function () {
        $signedUrl = URL::signedRoute('guest.access', ['guestLink' => 'nonexistent']);

        $response = $this->get($signedUrl);

        $response->assertNotFound();
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
        ]);

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
