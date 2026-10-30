<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true]);
    config(['features.file_uploads' => true]);
});

test('login page is accessible when feature flag is active', function () {
    $this->get('/login')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Login')
            ->has('googleRedirectUrl'));
});

test('login page returns 404 when feature flag is inactive', function () {
    Feature::purge(Authentication::class);
    config(['features.auth' => false]);

    $this->get('/login')->assertNotFound();
});

test('login page redirects authenticated users to home', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/login')->assertRedirect(route('home'));
});

test('google redirect sends user to google', function () {
    $response = $this->get('/auth/google/redirect');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

function signInWithGoogle(string $email, ?string $hostedDomain = 'example.com', bool $verified = true, string $id = '123456789', string $name = 'Test User'): TestResponse
{
    $socialiteUser = (new SocialiteUser)->setRaw(array_filter([
        'email_verified' => $verified,
        'hd' => $hostedDomain,
    ], fn ($value) => $value !== null));
    $socialiteUser->id = $id;
    $socialiteUser->name = $name;
    $socialiteUser->email = $email;
    $socialiteUser->avatar = 'https://lh3.googleusercontent.com/avatar';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    return test()->get('/auth/google/callback');
}

function assertSignInRejected(TestResponse $response): void
{
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Your account is not authorized to access this application.');
    expect(Auth::check())->toBeFalse()
        ->and(User::count())->toBe(0);
}

describe('Google sign-in with allowed domains', function () {
    beforeEach(function () {
        config([
            'features.allowed_email_domains' => ['example.com', 'partner.io'],
            'features.legacy_allowed_email_patterns' => false,
        ]);
    });

    test('a verified Workspace account on an allowed domain signs in', function () {
        signInWithGoogle('test@example.com')->assertRedirect(route('home'));

        expect(Auth::user()->email)->toBe('test@example.com')
            ->and(Auth::user()->provider)->toBe('google')
            ->and(Auth::user()->provider_id)->toBe('123456789')
            ->and(Auth::user()->avatar)->toBe('https://lh3.googleusercontent.com/avatar');
    });

    test('any listed domain is accepted, case-insensitively', function () {
        signInWithGoogle('User@Partner.IO', hostedDomain: 'partner.io')->assertRedirect(route('home'));
    });

    test('an existing user is updated on later sign-ins', function () {
        User::factory()->create(['email' => 'existing@example.com', 'name' => 'Old Name', 'provider' => 'google', 'provider_id' => '111222333']);

        signInWithGoogle('existing@example.com', id: '111222333', name: 'New Name');

        expect(User::count())->toBe(1)
            ->and(User::first()->name)->toBe('New Name');
    });

    test('other domains are rejected', function () {
        assertSignInRejected(signInWithGoogle('user@notallowed.com', hostedDomain: 'notallowed.com'));
    });

    test('look-alike domains are rejected', function () {
        assertSignInRejected(signInWithGoogle('user@example.com.evil.tld', hostedDomain: 'example.com.evil.tld'));
        assertSignInRejected(signInWithGoogle('user@notexample.com', hostedDomain: 'notexample.com'));
        assertSignInRejected(signInWithGoogle('user@sub.example.com', hostedDomain: 'sub.example.com'));
    });

    test('a personal Google account using a company address is rejected', function () {
        assertSignInRejected(signInWithGoogle('user@example.com', hostedDomain: null));
    });

    test('an account managed by another Workspace is rejected', function () {
        assertSignInRejected(signInWithGoogle('user@example.com', hostedDomain: 'other.org'));
    });

    test('an unverified email is rejected', function () {
        assertSignInRejected(signInWithGoogle('user@example.com', verified: false));
    });

    test('the Google account picker is limited to the domain when only one is allowed', function () {
        config(['features.allowed_email_domains' => ['example.com']]);

        expect($this->get('/auth/google/redirect')->headers->get('Location'))->toContain('hd=example.com');
    });
});

describe('Google sign-in misconfiguration fails closed', function () {
    test('nobody can sign in when no domain is configured', function () {
        config(['features.allowed_email_domains' => [], 'features.legacy_allowed_email_patterns' => false]);

        assertSignInRejected(signInWithGoogle('anyone@example.com'));
    });

    test('nobody can sign in while the old regex setting is still present', function () {
        config(['features.allowed_email_domains' => ['example.com'], 'features.legacy_allowed_email_patterns' => true]);

        assertSignInRejected(signInWithGoogle('test@example.com'));
    });

    test('nobody can sign in while an entry is not a plain domain', function () {
        config(['features.allowed_email_domains' => ['example.com', '/@partner\\.io$/i'], 'features.legacy_allowed_email_patterns' => false]);

        assertSignInRejected(signInWithGoogle('test@example.com'));
    });
});

test('logout clears session and redirects', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post('/auth/logout');

    $response->assertRedirect(route('home'));
    expect(Auth::check())->toBeFalse();
});

test('unsupported provider returns 404', function () {
    $this->get('/auth/github/redirect')->assertNotFound();
    $this->get('/auth/github/callback')->assertNotFound();
});

test('home page requires login when feature flag is active', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('home page does not require login when feature flag is inactive', function () {
    Feature::purge(Authentication::class);
    config(['features.auth' => false]);

    $this->get('/')->assertSuccessful();
});
