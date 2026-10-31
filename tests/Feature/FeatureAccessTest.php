<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\GuestLink;
use App\Models\Secret;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Laravel\Pennant\Feature;

function enableFeatures(bool $authentication, bool $fileUploads): void
{
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => $authentication, 'features.file_uploads' => $fileUploads]);
}

function fromTheSpa(): array
{
    return ['Referer' => 'http://localhost/'];
}

describe('secret creation when Authentication is on', function () {
    beforeEach(fn () => enableFeatures(authentication: true, fileUploads: true));

    test('anonymous API clients are rejected', function () {
        $this->postJson('/api/secrets', ['content' => 'ciphertext'])->assertUnauthorized();

        expect(Secret::count())->toBe(0);
    });

    test('anonymous browser sessions are rejected', function () {
        $this->postJson('/api/secrets', ['content' => 'ciphertext'], fromTheSpa())->assertUnauthorized();

        expect(Secret::count())->toBe(0);
    });

    test('signed-in users can create secrets', function () {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/secrets', ['content' => 'ciphertext'], fromTheSpa())
            ->assertSuccessful();
    });

    test('guests with a valid guest link can create secrets', function () {
        $guestLink = GuestLink::factory()->create();

        $this->withSession([
            'guest_link_id' => $guestLink->id,
            'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
        ])->postJson('/api/secrets', ['content' => 'ciphertext'], fromTheSpa())->assertSuccessful();
    });

    test('guests whose link expired are rejected', function () {
        $this->withSession([
            'guest_link_id' => 'expired-link',
            'guest_link_expires_at' => now()->subMinute()->toIso8601String(),
        ])->postJson('/api/secrets', ['content' => 'ciphertext'], fromTheSpa())->assertUnauthorized();
    });

    test('recipients can still check and retrieve without signing in', function () {
        $secret = Secret::factory()->create();

        $this->getJson("/api/secrets/{$secret->id}/check")->assertSuccessful();
        $this->postJson("/api/secrets/{$secret->id}/retrieve")->assertSuccessful();
    });
});

describe('secret creation when Authentication is off', function () {
    beforeEach(fn () => enableFeatures(authentication: false, fileUploads: false));

    test('anyone can create secrets', function () {
        $this->postJson('/api/secrets', ['content' => 'ciphertext'])->assertSuccessful();
    });
});

describe('authentication routes when Authentication is off', function () {
    beforeEach(fn () => enableFeatures(authentication: false, fileUploads: true));

    test('Google sign-in cannot be started or completed', function () {
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback?code=anything&state=anything')->assertNotFound();
    });

    test('guest links cannot be created or used', function () {
        $this->actingAs(User::factory()->create())->postJson('/api/guest-links')->assertNotFound();

        $guestLink = GuestLink::factory()->create();
        $this->get(URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]))->assertNotFound();
    });

    test('file uploads are closed even to an existing session', function () {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/files/initiate', [])
            ->assertNotFound();
    });
});

describe('file uploads when FileUploads is off', function () {
    beforeEach(fn () => enableFeatures(authentication: true, fileUploads: false));

    test('signed-in users cannot upload', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/files/initiate', [])->assertNotFound();
        $this->actingAs($user)->postJson('/api/files/some-upload/chunk', [])->assertNotFound();
        $this->actingAs($user)->postJson('/api/files/some-upload/complete')->assertNotFound();
    });
});
