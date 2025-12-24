<?php

use App\Models\Secret;
use Illuminate\Support\Facades\Hash;

test('secret uses string primary key', function () {
    $secret = new Secret;

    expect($secret->getKeyType())->toBe('string');
    expect($secret->incrementing)->toBeFalse();
});

test('secret generates unique 12-character ID on creation', function () {
    $secret = Secret::factory()->create();

    expect($secret->id)->toBeString();
    expect(strlen($secret->id))->toBe(12);
});

test('secret ID is alphanumeric', function () {
    $secret = Secret::factory()->create();

    expect($secret->id)->toMatch('/^[a-zA-Z0-9]{12}$/');
});

test('generated IDs are unique across multiple secrets', function () {
    $secrets = Secret::factory()->count(100)->create();

    $ids = $secrets->pluck('id')->toArray();
    $uniqueIds = array_unique($ids);

    expect(count($uniqueIds))->toBe(100);
});

test('custom ID is preserved when provided', function () {
    $customId = 'CustomId12Ab';

    $secret = Secret::factory()->create(['id' => $customId]);

    expect($secret->id)->toBe($customId);
});

test('password is automatically hashed via cast', function () {
    $plainPassword = 'mysecretpassword';

    $secret = Secret::factory()->create(['password' => $plainPassword]);
    $secret->refresh();

    // Password should be hashed, not plain text
    expect($secret->password)->not->toBe($plainPassword);
    // But it should verify correctly
    expect(Hash::check($plainPassword, $secret->password))->toBeTrue();
});

test('password hash uses bcrypt algorithm', function () {
    $secret = Secret::factory()->create(['password' => 'testpassword']);
    $secret->refresh();

    // Bcrypt hashes start with $2y$
    expect($secret->password)->toStartWith('$2y$');
});

test('null password remains null', function () {
    $secret = Secret::factory()->create(['password' => null]);

    expect($secret->password)->toBeNull();
});

test('isPasswordProtected returns true when password is set', function () {
    $secret = Secret::factory()->withPassword('test1234')->create();

    expect($secret->isPasswordProtected())->toBeTrue();
});

test('isPasswordProtected returns false when no password', function () {
    $secret = Secret::factory()->create(['password' => null]);

    expect($secret->isPasswordProtected())->toBeFalse();
});

test('requires_confirmation is cast to boolean', function () {
    $secretWithConfirmation = Secret::factory()->create(['requires_confirmation' => 1]);
    $secretWithoutConfirmation = Secret::factory()->create(['requires_confirmation' => 0]);

    expect($secretWithConfirmation->requires_confirmation)->toBeBool()->toBeTrue();
    expect($secretWithoutConfirmation->requires_confirmation)->toBeBool()->toBeFalse();
});

test('password attribute is hidden in serialization', function () {
    $secret = Secret::factory()->withPassword('secret123')->create();

    $array = $secret->toArray();

    expect($array)->not->toHaveKey('password');
});

test('olderThan scope filters secrets by creation date', function () {
    // Create secret "older" than 30 days
    $oldSecret = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $oldSecret->id)
        ->update(['created_at' => now()->subDays(31)]);

    // Create recent secret
    $newSecret = Secret::factory()->create();

    $oldSecrets = Secret::olderThan(30)->get();

    expect($oldSecrets)->toHaveCount(1);
    expect($oldSecrets->first()->id)->toBe($oldSecret->id);
});

test('olderThan scope returns empty when no old secrets exist', function () {
    Secret::factory()->count(3)->create();

    $oldSecrets = Secret::olderThan(30)->get();

    expect($oldSecrets)->toBeEmpty();
});

test('olderThan scope boundary condition: exactly 30 days is not included', function () {
    $exactlyThirtyDays = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $exactlyThirtyDays->id)
        ->update(['created_at' => now()->subDays(30)]);

    $thirtyOneDays = Secret::factory()->create();
    \Illuminate\Support\Facades\DB::table('secrets')
        ->where('id', $thirtyOneDays->id)
        ->update(['created_at' => now()->subDays(31)]);

    $oldSecrets = Secret::olderThan(30)->get();

    // Only 31-day-old secret should be included
    expect($oldSecrets)->toHaveCount(1);
    expect($oldSecrets->first()->id)->toBe($thirtyOneDays->id);
});

test('content is mass assignable', function () {
    $secret = Secret::create([
        'content' => 'test content',
    ]);

    expect($secret->content)->toBe('test content');
});

test('requires_confirmation is mass assignable', function () {
    $secret = Secret::create([
        'content' => 'test',
        'requires_confirmation' => true,
    ]);

    expect($secret->requires_confirmation)->toBeTrue();
});

test('password is mass assignable', function () {
    $secret = Secret::create([
        'content' => 'test',
        'password' => 'secretpass',
    ]);

    expect($secret->isPasswordProtected())->toBeTrue();
});

test('generateUniqueId produces unique IDs in isolation', function () {
    $ids = [];
    for ($i = 0; $i < 50; $i++) {
        $ids[] = Secret::generateUniqueId();
    }

    expect(array_unique($ids))->toHaveCount(50);
});

test('generateUniqueId avoids collision with existing IDs', function () {
    // Create a secret with a known ID pattern
    $existingSecret = Secret::factory()->create();

    // Generate a new ID - it should not match the existing one
    $newId = Secret::generateUniqueId();

    expect($newId)->not->toBe($existingSecret->id);
});
