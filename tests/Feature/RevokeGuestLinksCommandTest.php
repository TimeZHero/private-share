<?php

use App\Features\Authentication;
use App\Models\GuestLink;
use App\Models\User;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge(Authentication::class);
    config(['features.auth' => true]);
});

function guestCanEnter(string $cookie): bool
{
    return test()->withCookie('guest_access', $cookie)->get('/')->isSuccessful();
}

it('revokes one guest link by id', function () {
    $revoked = GuestLink::factory()->create();
    $untouched = GuestLink::factory()->create();
    $revokedCookie = acceptAndForgetSession($revoked);
    $untouchedCookie = acceptAndForgetSession($untouched);

    $this->artisan('guest-links:revoke', ['guestLink' => $revoked->id])
        ->expectsOutputToContain('Revoked 1 guest link.')
        ->assertSuccessful();

    expect(guestCanEnter($revokedCookie))->toBeFalse();
    test()->flushSession();
    expect(guestCanEnter($untouchedCookie))->toBeTrue();
});

it('fails when the id matches no active guest link', function () {
    $this->artisan('guest-links:revoke', ['guestLink' => 'missing'])
        ->expectsOutputToContain('No active guest link matched.')
        ->assertFailed();
});

it('revokes every guest link created by one user', function (string $userReference) {
    $creator = User::factory()->create(['id' => 41, 'email' => 'mario.rossi@example.com']);
    $otherCreator = User::factory()->create();
    $cookies = GuestLink::factory()->count(2)->for($creator, 'creator')->create()
        ->map(fn (GuestLink $guestLink) => acceptAndForgetSession($guestLink));
    $otherCookie = acceptAndForgetSession(GuestLink::factory()->for($otherCreator, 'creator')->create());

    $this->artisan('guest-links:revoke', ['--user' => $userReference])
        ->expectsOutputToContain('Revoked 2 guest links.')
        ->assertSuccessful();

    foreach ($cookies as $cookie) {
        test()->flushSession();
        expect(guestCanEnter($cookie))->toBeFalse();
    }
    test()->flushSession();
    expect(guestCanEnter($otherCookie))->toBeTrue();
})->with([
    'by email' => ['mario.rossi@example.com'],
    'by email in another case' => ['Mario.Rossi@Example.com'],
    'by id' => ['41'],
]);

it('fails when --user matches nobody', function () {
    $cookie = acceptAndForgetSession(GuestLink::factory()->create());

    $this->artisan('guest-links:revoke', ['--user' => 'nobody@example.com'])
        ->expectsOutputToContain('No user matches nobody@example.com.')
        ->assertFailed();

    expect(guestCanEnter($cookie))->toBeTrue();
});

it('revokes every active guest link after confirmation', function () {
    $cookie = acceptAndForgetSession(GuestLink::factory()->create());
    GuestLink::factory()->create();

    $this->artisan('guest-links:revoke', ['--all' => true])
        ->expectsConfirmation('Revoke all 2 active guest links?', 'yes')
        ->expectsOutputToContain('Revoked 2 guest links.')
        ->assertSuccessful();

    expect(guestCanEnter($cookie))->toBeFalse();
});

it('revokes nothing when --all is not confirmed', function () {
    $cookie = acceptAndForgetSession(GuestLink::factory()->create());

    $this->artisan('guest-links:revoke', ['--all' => true])
        ->expectsConfirmation('Revoke all 1 active guest links?', 'no')
        ->assertFailed();

    expect(guestCanEnter($cookie))->toBeTrue();
});

it('refuses to run without saying what to revoke', function () {
    $cookie = acceptAndForgetSession(GuestLink::factory()->create());

    $this->artisan('guest-links:revoke')
        ->expectsOutputToContain('Pass a guest link id, --user or --all.')
        ->assertFailed();

    expect(guestCanEnter($cookie))->toBeTrue();
});
