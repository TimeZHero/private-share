<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

test('google callback creates a new user and logs them in', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->name = 'Test User';
    $socialiteUser->email = 'test@example.com';
    $socialiteUser->avatar = 'https://lh3.googleusercontent.com/avatar';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('home'));
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->email)->toBe('test@example.com');
    expect(Auth::user()->provider)->toBe('google');
    expect(Auth::user()->provider_id)->toBe('123456789');
    expect(Auth::user()->avatar)->toBe('https://lh3.googleusercontent.com/avatar');

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'provider' => 'google',
        'provider_id' => '123456789',
    ]);
});

test('google callback updates existing user on subsequent login', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Old Name',
        'provider' => 'google',
        'provider_id' => '111222333',
    ]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '111222333';
    $socialiteUser->name = 'New Name';
    $socialiteUser->email = 'existing@example.com';
    $socialiteUser->avatar = 'https://lh3.googleusercontent.com/new-avatar';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $this->get('/auth/google/callback');

    expect(User::count())->toBe(1);
    expect(User::first()->name)->toBe('New Name');
    expect(User::first()->avatar)->toBe('https://lh3.googleusercontent.com/new-avatar');
});

test('google callback rejects unauthorized email when patterns are set', function () {
    config(['features.allowed_email_patterns' => ['/@allowed\.com$/i']]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '999888777';
    $socialiteUser->name = 'Rejected User';
    $socialiteUser->email = 'user@notallowed.com';
    $socialiteUser->avatar = null;

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Your email is not authorized to access this application.');
    expect(Auth::check())->toBeFalse();
    $this->assertDatabaseMissing('users', ['email' => 'user@notallowed.com']);
});

test('google callback allows authorized email when patterns are set', function () {
    config(['features.allowed_email_patterns' => ['/@example\.com$/i']]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '444555666';
    $socialiteUser->name = 'Allowed User';
    $socialiteUser->email = 'user@example.com';
    $socialiteUser->avatar = null;

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('home'));
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->email)->toBe('user@example.com');
});

test('google callback allows all emails when no patterns are set', function () {
    config(['features.allowed_email_patterns' => []]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '777888999';
    $socialiteUser->name = 'Any User';
    $socialiteUser->email = 'anyone@anything.org';
    $socialiteUser->avatar = null;

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $this->get('/auth/google/callback');
    expect(Auth::check())->toBeTrue();
});

test('google callback handles multiple email patterns', function () {
    config(['features.allowed_email_patterns' => ['/@example\.com$/i', '/@partner\.io$/i']]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '101010101';
    $socialiteUser->name = 'Partner User';
    $socialiteUser->email = 'user@partner.io';
    $socialiteUser->avatar = null;

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $this->get('/auth/google/callback');
    expect(Auth::check())->toBeTrue();
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
