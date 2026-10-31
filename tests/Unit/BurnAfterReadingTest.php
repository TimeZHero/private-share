<?php

use App\Models\Secret;
use App\Models\SharedFile;
use Illuminate\Support\Facades\Storage;

test('only the first of two concurrent burns of a secret wins', function () {
    $secret = Secret::factory()->create();
    $firstRequest = Secret::find($secret->id);
    $secondRequest = Secret::find($secret->id);

    expect($firstRequest->burn())->toBeTrue()
        ->and($secondRequest->burn())->toBeFalse()
        ->and(Secret::find($secret->id))->toBeNull();
});

test('burning a secret still records its deletion', function () {
    $secret = Secret::factory()->create();
    $deletedIds = [];
    Secret::deleted(function (Secret $deleted) use (&$deletedIds) {
        $deletedIds[] = $deleted->id;
    });

    $secret->burn();

    expect($deletedIds)->toBe([$secret->id])
        ->and($secret->exists)->toBeFalse();
});

test('a losing burn does not record a deletion', function () {
    $secret = Secret::factory()->create();
    $loser = Secret::find($secret->id);
    $secret->burn();
    $deletions = 0;
    Secret::deleted(function () use (&$deletions) {
        $deletions++;
    });

    $loser->burn();

    expect($deletions)->toBe(0);
});

test('claiming a shared file removes the row without touching its stored object', function () {
    Storage::fake(config('features.file_disk'));
    $sharedFile = SharedFile::factory()->create();
    Storage::disk(config('features.file_disk'))->put($sharedFile->storage_path, 'ciphertext');
    $concurrentRequest = SharedFile::find($sharedFile->id);

    expect($sharedFile->claim())->toBeTrue()
        ->and($concurrentRequest->claim())->toBeFalse()
        ->and(SharedFile::find($sharedFile->id))->toBeNull()
        ->and(Storage::disk(config('features.file_disk'))->exists($sharedFile->storage_path))->toBeTrue();
});
