<?php

use App\Models\Secret;

test('index page displays the secret creation form', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Private Share');
    $response->assertSee('Share Secret');
});

test('can create a secret via API', function () {
    $response = $this->postJson('/secrets', [
        'content' => 'encrypted-content-here',
    ]);

    $secret = Secret::first();
    expect($secret)->not->toBeNull();
    expect($secret->content)->toBe('encrypted-content-here');

    $response->assertSuccessful();
    $response->assertJson(['id' => $secret->id]);
});

test('secret id is a 12 character string', function () {
    $this->postJson('/secrets', [
        'content' => 'Test content',
    ]);

    $secret = Secret::first();
    expect($secret->id)->toBeString();
    expect(strlen($secret->id))->toBe(12);
});

test('can view a secret page', function () {
    $secret = Secret::factory()->create([
        'content' => 'encrypted-content',
    ]);

    $response = $this->get("/{$secret->id}");

    $response->assertStatus(200);
    $response->assertSee('Secret Content');
    $response->assertSee($secret->id);
});

test('creating a secret requires content', function () {
    $response = $this->postJson('/secrets', [
        'content' => '',
    ]);

    $response->assertUnprocessable();
    expect(Secret::count())->toBe(0);
});

test('viewing non-existent secret returns 404', function () {
    $response = $this->get('/non-existent-uuid-id');

    $response->assertNotFound();
});
