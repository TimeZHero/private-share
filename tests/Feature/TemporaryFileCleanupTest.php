<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\PendingUpload;
use App\Models\SharedFile;
use App\Models\User;
use Illuminate\Session\EncryptedStore;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

beforeEach(function () {
    File::ensureDirectoryExists(PendingUpload::temporaryDirectory());
});

afterEach(function () {
    foreach (createdTemporaryFiles() as $path) {
        @unlink($path);
    }
    createdTemporaryFiles(reset: true);
});

/** @return list<string> */
function createdTemporaryFiles(?string $add = null, bool $reset = false): array
{
    static $paths = [];

    if ($reset) {
        $paths = [];
    }

    if ($add !== null) {
        $paths[] = $add;
    }

    return $paths;
}

function temporaryFile(string $path, int $ageMinutes): string
{
    file_put_contents($path, 'ciphertext');
    touch($path, now()->subMinutes($ageMinutes)->getTimestamp());
    createdTemporaryFiles($path);

    return $path;
}

function pendingUpload(int $idleMinutes): PendingUpload
{
    $id = (string) Str::uuid();
    $upload = PendingUpload::create([
        'id' => $id,
        'user_id' => User::factory()->create()->id,
        'original_name' => 'file.bin',
        'mime_type' => 'application/octet-stream',
        'total_size' => 10,
        'total_chunks' => 1,
        'temp_path' => temporaryFile(PendingUpload::temporaryDirectory()."/{$id}", $idleMinutes),
        'encryption_salt' => 'salt',
        'client_iv' => 'iv',
    ]);
    $upload->forceFill(['created_at' => now()->subDays(2), 'updated_at' => now()->subMinutes($idleMinutes)])->saveQuietly();

    return $upload;
}

test('an upload with no chunk for over an hour is purged with its temp file', function () {
    $upload = pendingUpload(idleMinutes: 61);

    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(PendingUpload::find($upload->id))->toBeNull()
        ->and(file_exists($upload->temp_path))->toBeFalse();
});

test('a long upload that is still receiving chunks is kept, however old it is', function () {
    $upload = pendingUpload(idleMinutes: 5);

    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(PendingUpload::find($upload->id))->not->toBeNull()
        ->and(file_exists($upload->temp_path))->toBeTrue();
});

test('receiving a chunk keeps an upload alive', function () {
    $upload = pendingUpload(idleMinutes: 61);

    $upload->increment('received_chunks');

    expect(PendingUpload::idle()->whereKey($upload->id)->exists())->toBeFalse();
});

test('orphaned upload temp files older than an hour are swept', function () {
    $orphan = temporaryFile(PendingUpload::temporaryDirectory().'/'.Str::uuid(), 61);
    $fresh = temporaryFile(PendingUpload::temporaryDirectory().'/'.Str::uuid(), 5);

    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(file_exists($orphan))->toBeFalse()
        ->and(file_exists($fresh))->toBeTrue();
});

test('leftover download copies older than an hour are swept', function () {
    $leftover = temporaryFile(sys_get_temp_dir().'/'.SharedFile::DOWNLOAD_TEMP_PREFIX.Str::random(6), 61);
    $inFlight = temporaryFile(sys_get_temp_dir().'/'.SharedFile::DOWNLOAD_TEMP_PREFIX.Str::random(6), 5);
    $unrelated = temporaryFile(sys_get_temp_dir().'/other_'.Str::random(6), 61);

    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(file_exists($leftover))->toBeFalse()
        ->and(file_exists($inFlight))->toBeTrue()
        ->and(file_exists($unrelated))->toBeTrue();
});

test('the cleanup runs hourly', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('0 * * * *  php artisan secrets:cleanup');
});

test('the upload temp directory is private to the app user', function () {
    File::deleteDirectory(PendingUpload::temporaryDirectory());

    $this->actingAs(User::factory()->create());
    config(['features.auth' => true, 'features.file_uploads' => true]);
    Feature::purge([Authentication::class, FileUploads::class]);

    $this->postJson('/api/files/initiate', [
        'name' => 'file.bin',
        'mime_type' => 'application/octet-stream',
        'size' => 10,
        'total_chunks' => 1,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ])->assertCreated();

    expect(substr(sprintf('%o', fileperms(PendingUpload::temporaryDirectory())), -4))->toBe('0700');
});

test('sessions are always encrypted at rest', function () {
    expect(config('session.encrypt'))->toBeTrue()
        ->and(app('session')->driver())->toBeInstanceOf(EncryptedStore::class);
});
