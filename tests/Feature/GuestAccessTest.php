<?php

use App\Features\Authentication;
use App\Models\GuestLink;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge(Authentication::class);
    config(['features.auth' => true]);
    $this->travelTo('2026-11-07 10:00:00');
});

describe('Accepting a guest link', function () {
    it('remembers the guest in this browser until the link expires', function () {
        $guestLink = GuestLink::factory()->create(['expires_at' => '2026-11-14 10:00:00']);

        $response = $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]));

        $cookie = $response->getCookie('guest_access', decrypt: false);
        expect($cookie)->not->toBeNull()
            ->and($cookie->getExpiresTime())->toBe(1794650400)
            ->and($cookie->isHttpOnly())->toBeTrue()
            ->and($cookie->isSecure())->toBeTrue()
            ->and($cookie->getSameSite())->toBe('lax');
    });
});

describe('Returning after the session ended', function () {
    it('lets the guest back in with only the cookie', function () {
        $guestLink = GuestLink::factory()->create(['expires_at' => '2026-11-14 10:00:00']);
        $cookie = acceptAndForgetSession($guestLink);
        $this->travelTo('2026-11-13 09:00:00');

        $this->withCookie('guest_access', $cookie)->get('/')->assertSuccessful();

        expect(session('guest_link_id'))->toBe($guestLink->id);
    });

    it('issues a fresh session id when restoring the guest', function () {
        $cookie = acceptAndForgetSession(GuestLink::factory()->create());
        $this->startSession();
        $sessionIdBefore = session()->getId();

        $this->withCookie('guest_access', $cookie)->get('/')->assertSuccessful();

        expect(session()->getId())->not->toBe($sessionIdBefore);
    });

    it('lets the restored guest share a secret through the API', function () {
        $cookie = acceptAndForgetSession(GuestLink::factory()->create());

        $this->withCredentials()
            ->withCookie('guest_access', $cookie)
            ->postJson('/api/secrets', ['content' => 'encrypted'], ['Referer' => 'http://localhost/'])
            ->assertSuccessful();
    });

    it('ends access when the link expires', function () {
        $guestLink = GuestLink::factory()->create(['expires_at' => '2026-11-14 10:00:00']);
        $cookie = acceptAndForgetSession($guestLink);
        $this->travelTo('2026-11-14 10:00:01');

        $this->withCookie('guest_access', $cookie)->get('/')
            ->assertRedirect(route('login'))
            ->assertCookieExpired('guest_access');
    });

    it('rejects a cookie that was not issued by the app', function () {
        $this->withUnencryptedCookie('guest_access', 'forged|value')->get('/')
            ->assertRedirect(route('login'));
    });

    it('rejects a cookie pointed at another guest link', function () {
        $ownLink = GuestLink::factory()->create();
        $otherLink = GuestLink::factory()->create();
        $ownCookie = acceptAndForgetSession($ownLink);
        acceptAndForgetSession($otherLink);

        $this->withCookie('guest_access', str_replace($ownLink->id, $otherLink->id, $ownCookie))->get('/')
            ->assertRedirect(route('login'))
            ->assertCookieExpired('guest_access');
    });

    it('does not restore a guest whose link was revoked', function () {
        $guestLink = GuestLink::factory()->create();
        $cookie = acceptAndForgetSession($guestLink);
        DB::table('guest_links')->where('id', $guestLink->id)->update(['expires_at' => now()]);

        $this->withCookie('guest_access', $cookie)->get('/')
            ->assertRedirect(route('login'))
            ->assertCookieExpired('guest_access');
    });

    it('rejects a cookie with a guessed token', function () {
        $guestLink = GuestLink::factory()->create();
        acceptAndForgetSession($guestLink);

        $this->withCookie('guest_access', "{$guestLink->id}|".str_repeat('a', 64))->get('/')
            ->assertRedirect(route('login'))
            ->assertCookieExpired('guest_access');
    });
});

describe('Ending an active guest session', function () {
    it('ends the session on the next page once the link is revoked in the database', function () {
        $guestLink = GuestLink::factory()->create();
        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]));
        Cookie::flushQueuedCookies();
        $this->get('/')->assertSuccessful();

        DB::table('guest_links')->where('id', $guestLink->id)->update(['expires_at' => now()]);

        $this->get('/')
            ->assertRedirect(route('login'))
            ->assertCookieExpired('guest_access');
        expect(session('guest_link_id'))->toBeNull();
    });

    it('rejects the next API call once the link is revoked in the database', function () {
        $guestLink = GuestLink::factory()->create();
        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]));

        DB::table('guest_links')->where('id', $guestLink->id)->update(['expires_at' => now()]);

        $this->postJson('/api/secrets', ['content' => 'encrypted'], ['Referer' => 'http://localhost/'])
            ->assertUnauthorized();
    });

    it('forgets the guest in this browser on sign out', function () {
        $guestLink = GuestLink::factory()->create();
        $this->post(URL::signedRoute('guest.accept', ['guestLink' => $guestLink->id]));
        Cookie::flushQueuedCookies();

        $this->post(route('auth.logout'))->assertCookieExpired('guest_access');
    });
});
