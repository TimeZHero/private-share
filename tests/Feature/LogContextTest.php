<?php

use App\Features\Authentication;
use App\Models\GuestLink;
use App\Models\User;
use Illuminate\Support\Facades\Context;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Feature::purge(Authentication::class);
    config(['features.auth' => true]);
});

test('logs written while serving a request carry the signed-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/secrets', ['content' => 'ciphertext'], ['Referer' => 'http://localhost/'])->assertSuccessful();

    expect(Context::get('user_id'))->toBe($user->id)
        ->and(Context::get('guest_link_id'))->toBeNull()
        ->and(Context::get('request_id'))->toBeString();
});

test('logs written while serving a request carry the guest link', function () {
    $guestLink = GuestLink::factory()->create();

    $this->withSession([
        'guest_link_id' => $guestLink->id,
        'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
    ])->postJson('/api/secrets', ['content' => 'ciphertext'], ['Referer' => 'http://localhost/'])->assertSuccessful();

    expect(Context::get('guest_link_id'))->toBe($guestLink->id)
        ->and(Context::get('user_id'))->toBeNull();
});
