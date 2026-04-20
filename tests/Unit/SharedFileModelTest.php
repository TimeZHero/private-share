<?php

use App\Models\SharedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('features.file_disk'));
});

test('shared file uses string primary key', function () {
    $file = new SharedFile;

    expect($file->getKeyType())->toBe('string');
    expect($file->incrementing)->toBeFalse();
});

test('shared file generates unique 12-character ID on creation', function () {
    $file = SharedFile::factory()->create();

    expect($file->id)->toBeString();
    expect(strlen($file->id))->toBe(12);
});

test('shared file ID is alphanumeric', function () {
    $file = SharedFile::factory()->create();

    expect($file->id)->toMatch('/^[a-zA-Z0-9]{12}$/');
});

test('generated IDs are unique across multiple files', function () {
    $files = SharedFile::factory()->count(50)->create();

    $ids = $files->pluck('id')->toArray();

    expect(array_unique($ids))->toHaveCount(50);
});

test('storage_path is hidden in serialization', function () {
    $file = SharedFile::factory()->create();

    $array = $file->toArray();

    expect($array)->not->toHaveKey('storage_path');
});

test('formattedSize returns human readable sizes', function () {
    $fileBytes = SharedFile::factory()->create(['size' => 500]);
    expect($fileBytes->formattedSize())->toBe('500 B');

    $fileKb = SharedFile::factory()->create(['size' => 2048]);
    expect($fileKb->formattedSize())->toBe('2 KB');

    $fileMb = SharedFile::factory()->create(['size' => 5 * 1024 * 1024]);
    expect($fileMb->formattedSize())->toBe('5 MB');

    $fileGb = SharedFile::factory()->create(['size' => 2 * 1024 * 1024 * 1024]);
    expect($fileGb->formattedSize())->toBe('2 GB');
});

test('olderThan scope filters files by creation date', function () {
    $oldFile = SharedFile::factory()->create();
    \Illuminate\Support\Facades\DB::table('shared_files')
        ->where('id', $oldFile->id)
        ->update(['created_at' => now()->subDays(31)]);

    $newFile = SharedFile::factory()->create();

    $oldFiles = SharedFile::olderThan(30)->get();

    expect($oldFiles)->toHaveCount(1);
    expect($oldFiles->first()->id)->toBe($oldFile->id);
});

test('olderThan scope returns empty when no old files exist', function () {
    SharedFile::factory()->count(3)->create();

    expect(SharedFile::olderThan(30)->get())->toBeEmpty();
});

test('size is cast to integer', function () {
    $file = SharedFile::factory()->create(['size' => '1048576']);

    expect($file->size)->toBeInt();
    expect($file->size)->toBe(1048576);
});
