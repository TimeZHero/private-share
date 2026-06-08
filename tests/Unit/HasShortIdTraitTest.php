<?php

use App\Models\Secret;
use App\Models\SharedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('features.file_disk'));
});

test('Secret still uses string primary key via trait', function () {
    $secret = new Secret;

    expect($secret->getKeyType())->toBe('string');
    expect($secret->incrementing)->toBeFalse();
});

test('SharedFile still uses string primary key via trait', function () {
    $file = new SharedFile;

    expect($file->getKeyType())->toBe('string');
    expect($file->incrementing)->toBeFalse();
});

test('Secret still generates 12-char alphanumeric ID on creation', function () {
    $secret = Secret::factory()->create();

    expect($secret->id)->toBeString();
    expect($secret->id)->toMatch('/^[a-zA-Z0-9]{12}$/');
});

test('SharedFile still generates 12-char alphanumeric ID on creation', function () {
    $file = SharedFile::factory()->create();

    expect($file->id)->toBeString();
    expect($file->id)->toMatch('/^[a-zA-Z0-9]{12}$/');
});

test('Secret preserves custom ID when provided', function () {
    $secret = Secret::factory()->create(['id' => 'CustomId12Ab']);

    expect($secret->id)->toBe('CustomId12Ab');
});

test('SharedFile preserves custom ID when provided', function () {
    $file = SharedFile::factory()->create(['id' => 'CustomId12Ab']);

    expect($file->id)->toBe('CustomId12Ab');
});

test('both models generate distinct IDs', function () {
    $secrets = Secret::factory()->count(20)->create();
    $files = SharedFile::factory()->count(20)->create();

    $allIds = $secrets->pluck('id')->merge($files->pluck('id'))->toArray();

    expect(array_unique($allIds))->toHaveCount(40);
});
