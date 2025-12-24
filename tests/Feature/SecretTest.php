<?php

use App\Models\Secret;

test('index page displays the secret creation form', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee(config('app.name'));
    $response->assertSee('Share Secret');
});

test('can create a secret via API', function () {
    $response = $this->postJson('/api/secrets', [
        'content' => 'encrypted-content-here',
    ]);

    $secret = Secret::first();
    expect($secret)->not->toBeNull();
    expect($secret->content)->toBe('encrypted-content-here');

    $response->assertSuccessful();
    $response->assertJson(['id' => $secret->id]);
});

test('can create a secret with confirmation required', function () {
    $response = $this->postJson('/api/secrets', [
        'content' => 'encrypted-content-here',
        'requires_confirmation' => true,
    ]);

    $response->assertSuccessful();

    $secret = Secret::first();
    expect($secret)->not->toBeNull();
    expect($secret->requires_confirmation)->toBeTrue();
});

test('can create a secret with password protection', function () {
    $response = $this->postJson('/api/secrets', [
        'content' => 'encrypted-content-here',
        'password' => 'mypassword123',
    ]);

    $response->assertSuccessful();

    $secret = Secret::first();
    expect($secret)->not->toBeNull();
    expect($secret->isPasswordProtected())->toBeTrue();
    // Password should be hashed, not stored in plain text
    expect($secret->password)->not->toBe('mypassword123');
});

test('password must be at least 4 characters', function () {
    $response = $this->postJson('/api/secrets', [
        'content' => 'encrypted-content-here',
        'password' => 'abc',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('password');
});

test('secret id is a 12 character string', function () {
    $this->postJson('/api/secrets', [
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
    $secretId = $secret->id;

    $response = $this->get("/{$secretId}");

    $response->assertStatus(200);
    $response->assertSee($secretId);
});

test('can check secret requirements', function () {
    $secret = Secret::factory()->create();

    $response = $this->getJson("/api/secrets/{$secret->id}/check");

    $response->assertSuccessful();
    $response->assertJson([
        'requires_confirmation' => false,
        'requires_password' => false,
    ]);
});

test('check returns correct flags for protected secret', function () {
    $secret = Secret::factory()->requiresConfirmation()->withPassword()->create();

    $response = $this->getJson("/api/secrets/{$secret->id}/check");

    $response->assertSuccessful();
    $response->assertJson([
        'requires_confirmation' => true,
        'requires_password' => true,
    ]);
});

test('can retrieve a secret via API', function () {
    $secret = Secret::factory()->create([
        'content' => 'encrypted-content',
    ]);
    $secretId = $secret->id;

    $response = $this->postJson("/api/secrets/{$secretId}/retrieve");

    $response->assertSuccessful();
    $response->assertJson([
        'content' => 'encrypted-content',
    ]);

    // Secret should be deleted after retrieval
    expect(Secret::find($secretId))->toBeNull();
});

test('secret is deleted after being retrieved', function () {
    $secret = Secret::factory()->create([
        'content' => 'encrypted-content',
    ]);
    $secretId = $secret->id;

    $this->postJson("/api/secrets/{$secretId}/retrieve")->assertSuccessful();

    expect(Secret::find($secretId))->toBeNull();
});

test('secret cannot be retrieved twice', function () {
    $secret = Secret::factory()->create([
        'content' => 'encrypted-content',
    ]);
    $secretId = $secret->id;

    $this->postJson("/api/secrets/{$secretId}/retrieve")->assertSuccessful();

    $this->postJson("/api/secrets/{$secretId}/retrieve")->assertNotFound();
});

test('password protected secret requires correct password', function () {
    $secret = Secret::factory()->withPassword('correctpassword')->create();

    // Without password
    $response = $this->postJson("/api/secrets/{$secret->id}/retrieve");
    $response->assertUnprocessable();

    // With wrong password
    $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
        'password' => 'wrongpassword',
    ]);
    $response->assertForbidden();
    $response->assertJson([
        'error' => 'invalid_password',
    ]);

    // Secret should still exist
    expect(Secret::find($secret->id))->not->toBeNull();

    // With correct password
    $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
        'password' => 'correctpassword',
    ]);
    $response->assertSuccessful();

    // Now it should be deleted
    expect(Secret::find($secret->id))->toBeNull();
});

test('creating a secret requires content', function () {
    $response = $this->postJson('/api/secrets', [
        'content' => '',
    ]);

    $response->assertUnprocessable();
    expect(Secret::count())->toBe(0);
});

test('viewing non-existent secret returns 404', function () {
    $response = $this->get('/non-existent-uuid-id');

    $response->assertNotFound();
});

test('retrieving non-existent secret returns 404', function () {
    $response = $this->postJson('/api/secrets/non-existent-id/retrieve');

    $response->assertNotFound();
});

test('checking non-existent secret returns 404', function () {
    $response = $this->getJson('/api/secrets/non-existent-id/check');

    $response->assertNotFound();
});

test('secrets cleanup command deletes secrets older than 30 days', function () {
    // Create an old secret (31 days ago) - use DB facade to bypass model timestamps
    $oldSecret = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $oldSecret->id)
        ->update(['created_at' => now()->subDays(31)]);

    // Create a recent secret (29 days ago)
    $recentSecret = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $recentSecret->id)
        ->update(['created_at' => now()->subDays(29)]);

    // Create a brand new secret
    $newSecret = Secret::factory()->create();

    expect(Secret::count())->toBe(3);

    // Run the cleanup command
    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(Secret::count())->toBe(2);
    expect(Secret::find($oldSecret->id))->toBeNull();
    expect(Secret::find($recentSecret->id))->not->toBeNull();
    expect(Secret::find($newSecret->id))->not->toBeNull();
});

test('secrets cleanup command handles no expired secrets gracefully', function () {
    // Create only recent secrets
    Secret::factory()->count(3)->create();

    $this->artisan('secrets:cleanup')
        ->expectsOutputToContain('Deleted 0 expired secrets')
        ->assertSuccessful();

    expect(Secret::count())->toBe(3);
});
