<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\PendingUpload;
use App\Models\Secret;
use App\Models\SharedFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Storage::fake(config('features.file_disk'));
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true]);
    config(['features.file_uploads' => true]);
    $this->actingAs(User::factory()->create());

    /**
     * Helper: run a full chunked upload and return the shared_file_id.
     */
    $this->chunkedUpload = function (array $initiateOverrides = [], ?UploadedFile $file = null): string {
        $file ??= UploadedFile::fake()->createWithContent('document.pdf', random_bytes(1024));
        $chunkSize = 8 * 1024 * 1024;

        $content = file_get_contents($file->getRealPath());
        $totalChunks = max(1, (int) ceil(strlen($content) / $chunkSize));

        $encryptionSalt = base64_encode(random_bytes(16));
        $clientIv = base64_encode(random_bytes(12));
        $hasEncryption = true;

        if (isset($initiateOverrides['encryption_salt']) && $initiateOverrides['encryption_salt'] === null) {
            $hasEncryption = false;
            unset($initiateOverrides['encryption_salt'], $initiateOverrides['client_iv']);
        }

        $initPayload = array_merge([
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => strlen($content),
            'total_chunks' => $totalChunks,
            'encryption_salt' => $encryptionSalt,
            'client_iv' => $clientIv,
        ], $initiateOverrides);

        $initResponse = $this->postJson('/api/files/initiate', $initPayload);
        $initResponse->assertCreated();
        $uploadId = $initResponse->json('upload_id');

        for ($index = 0; $index < $totalChunks; $index++) {
            $plainChunk = substr($content, $index * $chunkSize, $chunkSize);

            if ($hasEncryption) {
                $salt = base64_decode($initPayload['encryption_salt'], true);
                $baseIv = base64_decode($initPayload['client_iv'], true);
                $derivedKey = hash_pbkdf2('sha256', 'testkey', $salt, 100000, 32, true);

                $chunkIv = $baseIv;
                $indexBytes = pack('V', $index).str_repeat("\0", 8);
                for ($byte = 0; $byte < 12; $byte++) {
                    $chunkIv[$byte] = chr(ord($baseIv[$byte]) ^ ord($indexBytes[$byte]));
                }

                $tag = '';
                $ciphertext = openssl_encrypt($plainChunk, 'aes-256-gcm', $derivedKey, OPENSSL_RAW_DATA, $chunkIv, $tag, '', 16);
                $chunkData = $ciphertext.$tag;
            } else {
                $chunkData = $plainChunk;
            }

            $chunkFile = UploadedFile::fake()->createWithContent("chunk_{$index}", $chunkData);

            $this->postJson("/api/files/{$uploadId}/chunk", [
                'chunk_index' => $index,
                'chunk' => $chunkFile,
            ])->assertSuccessful();
        }

        $completeResponse = $this->postJson("/api/files/{$uploadId}/complete");
        $completeResponse->assertCreated();

        return $completeResponse->json('shared_file_id');
    };

    /**
     * Helper: upload a file and create a secret linked to it.
     */
    $this->createFileSecret = function (array $secretOverrides = [], array $initiateOverrides = []): Secret {
        $sharedFileId = ($this->chunkedUpload)($initiateOverrides);

        $payload = array_merge([
            'shared_file_id' => $sharedFileId,
            'password' => 'testpassword',
        ], $secretOverrides);

        $response = $this->postJson('/api/secrets', $payload);
        $response->assertSuccessful();

        return Secret::find($response->json('id'));
    };
});

// --- Feature flag gating ---

test('home page passes file uploads enabled prop when feature flag is active', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('fileUploadsEnabled', true)
            ->has('maxSizeGb'));
});

test('home page redirects to login when auth feature is enabled but user is not authenticated', function () {
    Auth::logout();

    $this->get('/')->assertRedirect(route('login'));
});

test('home page passes file uploads disabled prop when feature flag is inactive', function () {
    Feature::purge(FileUploads::class);
    config(['features.file_uploads' => false]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('fileUploadsEnabled', false));
});

test('unauthenticated users cannot initiate file uploads', function () {
    Auth::logout();

    $this->postJson('/api/files/initiate', [
        'name' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'total_chunks' => 1,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ])->assertUnauthorized();
});

// --- Chunked upload: initiate ---

test('can initiate a chunked upload', function () {
    $response = $this->postJson('/api/files/initiate', [
        'name' => 'bigfile.zip',
        'mime_type' => 'application/zip',
        'size' => 16 * 1024 * 1024,
        'total_chunks' => 2,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['upload_id']);

    $pending = PendingUpload::find($response->json('upload_id'));
    expect($pending)->not->toBeNull();
    expect($pending->encryption_salt)->not->toBeEmpty();
    expect($pending->client_iv)->not->toBeEmpty();
});

test('initiate validates required fields', function () {
    $this->postJson('/api/files/initiate', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'mime_type', 'size', 'total_chunks', 'encryption_salt', 'client_iv']);
});

test('initiate rejects files exceeding max size', function () {
    $maxGb = config('features.file_max_size_gb', 10);
    $overSize = ($maxGb * 1024 * 1024 * 1024) + 1;

    $this->postJson('/api/files/initiate', [
        'name' => 'huge.bin',
        'mime_type' => 'application/octet-stream',
        'size' => $overSize,
        'total_chunks' => 100,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('size');
});

// --- Chunked upload: chunk ---

test('can upload a chunk', function () {
    $initResponse = $this->postJson('/api/files/initiate', [
        'name' => 'test.txt',
        'mime_type' => 'text/plain',
        'size' => 100,
        'total_chunks' => 1,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ]);

    $uploadId = $initResponse->json('upload_id');
    $chunkFile = UploadedFile::fake()->createWithContent('chunk', 'hello world');

    $response = $this->postJson("/api/files/{$uploadId}/chunk", [
        'chunk_index' => 0,
        'chunk' => $chunkFile,
    ]);

    $response->assertSuccessful();
    expect($response->json('received_chunks'))->toBe(1);
});

test('rejects out-of-order chunks', function () {
    $initResponse = $this->postJson('/api/files/initiate', [
        'name' => 'test.txt',
        'mime_type' => 'text/plain',
        'size' => 200,
        'total_chunks' => 2,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ]);

    $uploadId = $initResponse->json('upload_id');
    $chunkFile = UploadedFile::fake()->createWithContent('chunk', 'data');

    $this->postJson("/api/files/{$uploadId}/chunk", [
        'chunk_index' => 1,
        'chunk' => $chunkFile,
    ])->assertStatus(409);
});

// --- Chunked upload: complete ---

test('full chunked upload produces a shared file', function () {
    $sharedFileId = ($this->chunkedUpload)();

    $sharedFile = SharedFile::find($sharedFileId);
    expect($sharedFile)->not->toBeNull();
    expect($sharedFile->original_name)->toBe('document.pdf');
    expect($sharedFile->mime_type)->toBe('application/pdf');
    expect($sharedFile->encryption_salt)->not->toBeEmpty();
    expect($sharedFile->client_iv)->not->toBeEmpty();
    expect($sharedFile->client_encrypted)->toBeTrue();

    Storage::disk(config('features.file_disk'))->assertExists($sharedFile->storage_path);
    expect(PendingUpload::count())->toBe(0);
});

test('complete rejects incomplete upload', function () {
    $initResponse = $this->postJson('/api/files/initiate', [
        'name' => 'test.txt',
        'mime_type' => 'text/plain',
        'size' => 200,
        'total_chunks' => 2,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ]);

    $uploadId = $initResponse->json('upload_id');

    $this->postJson("/api/files/{$uploadId}/complete")
        ->assertStatus(409)
        ->assertJson(['error' => 'upload_incomplete']);
});

// --- Secret with file ---

test('can create a secret linked to a shared file', function () {
    $sharedFileId = ($this->chunkedUpload)();

    $response = $this->postJson('/api/secrets', [
        'shared_file_id' => $sharedFileId,
        'password' => 'testpassword',
    ]);

    $response->assertSuccessful();
    $secret = Secret::find($response->json('id'));
    expect($secret->shared_file_id)->toBe($sharedFileId);
    expect($secret->hasFile())->toBeTrue();
    expect($secret->content)->toBeNull();
    expect($secret->isPasswordProtected())->toBeTrue();
});

test('creating a file secret without password fails validation', function () {
    $sharedFileId = ($this->chunkedUpload)();

    $this->postJson('/api/secrets', [
        'shared_file_id' => $sharedFileId,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

test('can create a secret with both text and file', function () {
    $sharedFileId = ($this->chunkedUpload)();

    $response = $this->postJson('/api/secrets', [
        'content' => 'Here is a message with a file.',
        'shared_file_id' => $sharedFileId,
        'password' => 'testpassword',
    ]);

    $response->assertSuccessful();
    $secret = Secret::find($response->json('id'));
    expect($secret->content)->toBe('Here is a message with a file.');
    expect($secret->hasFile())->toBeTrue();
    expect($secret->isPasswordProtected())->toBeTrue();
});

test('check endpoint returns file info for secret with file', function () {
    $secret = ($this->createFileSecret)();

    $response = $this->getJson("/api/secrets/{$secret->id}/check");

    $response->assertSuccessful();
    $response->assertJson([
        'has_file' => true,
        'requires_password' => true,
    ]);
    $response->assertJsonStructure(['file' => ['original_name', 'size', 'formatted_size', 'mime_type', 'client_encrypted', 'encryption_salt', 'client_iv']]);
});

test('retrieve returns file_id for secret with file', function () {
    $secret = ($this->createFileSecret)();
    $sharedFileId = $secret->shared_file_id;

    $response = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
        'password' => 'testpassword',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'has_file' => true,
        'file_id' => $sharedFileId,
    ]);

    expect(Secret::find($secret->id))->toBeNull();
    expect(SharedFile::find($sharedFileId))->not->toBeNull();
});

test('retrieve burns text-only secret immediately', function () {
    $secret = Secret::factory()->create(['content' => 'text only']);

    $this->postJson("/api/secrets/{$secret->id}/retrieve")->assertSuccessful();

    expect(Secret::find($secret->id))->toBeNull();
});

test('retrieve burns file secret but keeps shared file for download', function () {
    $secret = ($this->createFileSecret)();
    $sharedFileId = $secret->shared_file_id;

    $this->postJson("/api/secrets/{$secret->id}/retrieve", ['password' => 'testpassword'])->assertSuccessful();

    expect(Secret::find($secret->id))->toBeNull();
    expect(SharedFile::find($sharedFileId))->not->toBeNull();
});

// --- Download ---

test('can download a file via shared file id', function () {
    $secret = ($this->createFileSecret)();

    $response = $this->postJson("/api/files/{$secret->shared_file_id}/download");

    $response->assertSuccessful();
    $response->assertHeader('content-disposition');
    $response->assertHeader('x-client-encrypted', '1');
    $response->assertHeader('x-encryption-salt');
    $response->assertHeader('x-client-iv');
    $response->assertHeader('x-plaintext-size');
});

test('uploaded bytes are stored on disk without modification', function () {
    $originalContent = random_bytes(2048);
    $salt = random_bytes(16);
    $baseIv = random_bytes(12);

    $derivedKey = hash_pbkdf2('sha256', 'testkey', $salt, 100000, 32, true);
    $chunkIv = $baseIv;
    $indexBytes = pack('V', 0).str_repeat("\0", 8);
    for ($byte = 0; $byte < 12; $byte++) {
        $chunkIv[$byte] = chr(ord($baseIv[$byte]) ^ ord($indexBytes[$byte]));
    }
    $tag = '';
    $ciphertext = openssl_encrypt($originalContent, 'aes-256-gcm', $derivedKey, OPENSSL_RAW_DATA, $chunkIv, $tag, '', 16);
    $encryptedContent = $ciphertext.$tag;

    $initResponse = $this->postJson('/api/files/initiate', [
        'name' => 'test.bin',
        'mime_type' => 'application/octet-stream',
        'size' => strlen($originalContent),
        'total_chunks' => 1,
        'encryption_salt' => base64_encode($salt),
        'client_iv' => base64_encode($baseIv),
    ]);
    $initResponse->assertCreated();
    $uploadId = $initResponse->json('upload_id');

    $file = UploadedFile::fake()->createWithContent('test.bin', $encryptedContent);
    $this->postJson("/api/files/{$uploadId}/chunk", [
        'chunk_index' => 0,
        'chunk' => $file,
    ])->assertSuccessful();

    $completeResponse = $this->postJson("/api/files/{$uploadId}/complete");
    $completeResponse->assertCreated();
    $sharedFileId = $completeResponse->json('shared_file_id');

    $sharedFile = SharedFile::find($sharedFileId);
    $disk = Storage::disk(config('features.file_disk'));

    expect($disk->exists($sharedFile->storage_path))->toBeTrue();
    expect($disk->size($sharedFile->storage_path))->toBe(strlen($encryptedContent));
    expect($disk->get($sharedFile->storage_path))->toBe($encryptedContent);
});

test('download burns shared file after streaming', function () {
    $secret = ($this->createFileSecret)();
    $sharedFileId = $secret->shared_file_id;
    $storagePath = $secret->sharedFile->storage_path;

    $this->postJson("/api/files/{$sharedFileId}/download")->assertSuccessful();

    expect(SharedFile::find($sharedFileId))->toBeNull();
    Storage::disk(config('features.file_disk'))->assertMissing($storagePath);
});

test('file cannot be downloaded twice', function () {
    $secret = ($this->createFileSecret)();

    $this->postJson("/api/files/{$secret->shared_file_id}/download")->assertSuccessful();
    $this->postJson("/api/files/{$secret->shared_file_id}/download")->assertNotFound();
});

test('full lifecycle: create → retrieve → download → everything burned', function () {
    $secret = ($this->createFileSecret)(['content' => 'secret text with file']);
    $secretId = $secret->id;
    $sharedFileId = $secret->shared_file_id;
    $storagePath = $secret->sharedFile->storage_path;

    $this->postJson("/api/secrets/{$secretId}/retrieve", ['password' => 'testpassword'])
        ->assertSuccessful()
        ->assertJson(['has_file' => true, 'file_id' => $sharedFileId, 'content' => 'secret text with file']);

    expect(Secret::find($secretId))->toBeNull('Secret should be burned after retrieve');
    expect(SharedFile::find($sharedFileId))->not->toBeNull('SharedFile should survive until download');
    Storage::disk(config('features.file_disk'))->assertExists($storagePath);

    $this->postJson("/api/files/{$sharedFileId}/download")
        ->assertSuccessful()
        ->assertHeader('content-disposition');

    expect(SharedFile::find($sharedFileId))->toBeNull('SharedFile should be burned after download');
    Storage::disk(config('features.file_disk'))->assertMissing($storagePath);
});

test('downloading non-existent file returns 404', function () {
    $this->postJson('/api/files/nonexistent123/download')->assertNotFound();
});

// --- Password protected file secret ---

test('password protected file secret requires correct password at retrieval', function () {
    $sharedFileId = ($this->chunkedUpload)();

    $this->postJson('/api/secrets', [
        'shared_file_id' => $sharedFileId,
        'password' => 'correctpassword',
    ])->assertSuccessful();

    $secret = Secret::whereNotNull('shared_file_id')->first();

    $this->postJson("/api/secrets/{$secret->id}/retrieve")
        ->assertUnprocessable();

    $this->postJson("/api/secrets/{$secret->id}/retrieve", ['password' => 'wrong'])
        ->assertForbidden()
        ->assertJson(['error' => 'invalid_password']);

    $this->postJson("/api/secrets/{$secret->id}/retrieve", ['password' => 'correctpassword'])
        ->assertSuccessful()
        ->assertJson(['has_file' => true]);
});

// --- Cleanup ---

test('cleanup command deletes shared files older than 30 days', function () {
    $oldFile = SharedFile::factory()->create();
    \Illuminate\Support\Facades\DB::table('shared_files')
        ->where('id', $oldFile->id)
        ->update(['created_at' => now()->subDays(31)]);

    $recentFile = SharedFile::factory()->create();

    expect(SharedFile::count())->toBe(2);

    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(SharedFile::count())->toBe(1);
    expect(SharedFile::find($oldFile->id))->toBeNull();
    expect(SharedFile::find($recentFile->id))->not->toBeNull();
});

test('E2E crypto round-trip: PHP mimics JS encrypt → upload → download → decrypt', function () {
    $chunkSize = 8 * 1024 * 1024;

    $plaintext = random_bytes(5000);
    $plaintextSize = strlen($plaintext);

    $salt = random_bytes(16);
    $saltB64 = base64_encode($salt);
    $baseIv = random_bytes(12);
    $baseIvB64 = base64_encode($baseIv);
    $fileKey = 'AbCdEfGh';

    $derivedKey = hash_pbkdf2('sha256', $fileKey, $salt, 100000, 32, true);

    $totalChunks = (int) ceil($plaintextSize / $chunkSize);

    $initResponse = $this->postJson('/api/files/initiate', [
        'name' => 'test.bin',
        'mime_type' => 'application/octet-stream',
        'size' => $plaintextSize,
        'total_chunks' => $totalChunks,
        'encryption_salt' => $saltB64,
        'client_iv' => $baseIvB64,
    ]);
    $initResponse->assertCreated();
    $uploadId = $initResponse->json('upload_id');

    for ($chunkIdx = 0; $chunkIdx < $totalChunks; $chunkIdx++) {
        $start = $chunkIdx * $chunkSize;
        $chunkPlaintext = substr($plaintext, $start, $chunkSize);

        $chunkIv = $baseIv;
        $indexBytes = pack('V', $chunkIdx).str_repeat("\0", 8);
        for ($byte = 0; $byte < 12; $byte++) {
            $chunkIv[$byte] = chr(ord($baseIv[$byte]) ^ ord($indexBytes[$byte]));
        }

        $tag = '';
        $ciphertext = openssl_encrypt($chunkPlaintext, 'aes-256-gcm', $derivedKey, OPENSSL_RAW_DATA, $chunkIv, $tag, '', 16);
        $encryptedChunk = $ciphertext.$tag;

        $chunkFile = UploadedFile::fake()->createWithContent("chunk_{$chunkIdx}", $encryptedChunk);
        $this->postJson("/api/files/{$uploadId}/chunk", [
            'chunk_index' => $chunkIdx,
            'chunk' => $chunkFile,
        ])->assertSuccessful();
    }

    $completeResponse = $this->postJson("/api/files/{$uploadId}/complete");
    $completeResponse->assertCreated();
    $sharedFileId = $completeResponse->json('shared_file_id');

    $this->postJson('/api/secrets', [
        'shared_file_id' => $sharedFileId,
        'password' => 'testpass',
    ])->assertSuccessful();

    $secret = Secret::whereNotNull('shared_file_id')->first();

    $checkResponse = $this->getJson("/api/secrets/{$secret->id}/check");
    $checkResponse->assertSuccessful();
    expect($checkResponse->json('has_file'))->toBeTrue();
    expect($checkResponse->json('file.client_encrypted'))->toBeTrue();
    expect($checkResponse->json('file.encryption_salt'))->toBe($saltB64);
    expect($checkResponse->json('file.client_iv'))->toBe($baseIvB64);

    $retrieveResponse = $this->postJson("/api/secrets/{$secret->id}/retrieve", [
        'password' => 'testpass',
    ]);
    $retrieveResponse->assertSuccessful();
    $downloadFileId = $retrieveResponse->json('file_id');

    expect(Secret::find($secret->id))->toBeNull('Secret should be burned after retrieve');
    expect(SharedFile::find($downloadFileId))->not->toBeNull('SharedFile should survive for download');

    $sharedFile = SharedFile::find($downloadFileId);
    $downloadedBytes = Storage::disk(config('features.file_disk'))->get($sharedFile->storage_path);
    $expectedEncryptedSize = $plaintextSize + ($totalChunks * 16);

    $downloadResponse = $this->postJson("/api/files/{$downloadFileId}/download");
    $downloadResponse->assertSuccessful();
    expect($downloadResponse->headers->get('X-Client-Encrypted'))->toBe('1');
    expect($downloadResponse->headers->get('X-Encryption-Salt'))->toBe($saltB64);
    expect($downloadResponse->headers->get('X-Client-Iv'))->toBe($baseIvB64);
    expect($downloadResponse->headers->get('X-Plaintext-Size'))->toBe((string) $plaintextSize);

    $decryptedParts = '';
    $offset = 0;
    $fullChunks = intdiv($plaintextSize, $chunkSize);
    $remainder = $plaintextSize % $chunkSize;
    $decryptTotalChunks = $fullChunks + ($remainder > 0 ? 1 : 0);

    for ($idx = 0; $idx < $decryptTotalChunks; $idx++) {
        $isLast = ($idx === $decryptTotalChunks - 1) && $remainder > 0;
        $needed = $isLast ? $remainder + 16 : $chunkSize + 16;
        $encChunk = substr($downloadedBytes, $offset, $needed);
        $offset += $needed;

        $chunkIv = $baseIv;
        $indexBytes = pack('V', $idx).str_repeat("\0", 8);
        for ($byte = 0; $byte < 12; $byte++) {
            $chunkIv[$byte] = chr(ord($baseIv[$byte]) ^ ord($indexBytes[$byte]));
        }

        $tag = substr($encChunk, -16);
        $ct = substr($encChunk, 0, -16);
        $decrypted = openssl_decrypt($ct, 'aes-256-gcm', $derivedKey, OPENSSL_RAW_DATA, $chunkIv, $tag, '');
        expect($decrypted)->not->toBeFalse("Decryption of chunk {$idx} should succeed");
        $decryptedParts .= $decrypted;
    }

    expect(strlen($decryptedParts))->toBe($plaintextSize);
    expect($decryptedParts)->toBe($plaintext, 'Decrypted content should match original plaintext');

    expect(SharedFile::find($downloadFileId))->toBeNull('SharedFile should be burned after download');
});

test('cleanup command deletes stale pending uploads', function () {
    $stale = PendingUpload::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'original_name' => 'stale.bin',
        'mime_type' => 'application/octet-stream',
        'total_size' => 1024,
        'total_chunks' => 1,
        'temp_path' => storage_path('app/private/uploads/stale-test'),
    ]);
    \Illuminate\Support\Facades\DB::table('pending_uploads')
        ->where('id', $stale->id)
        ->update(['created_at' => now()->subHours(25)]);

    $recent = PendingUpload::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'original_name' => 'recent.bin',
        'mime_type' => 'application/octet-stream',
        'total_size' => 1024,
        'total_chunks' => 1,
        'temp_path' => storage_path('app/private/uploads/recent-test'),
    ]);

    expect(PendingUpload::count())->toBe(2);

    $this->artisan('secrets:cleanup')->assertSuccessful();

    expect(PendingUpload::count())->toBe(1);
    expect(PendingUpload::find($stale->id))->toBeNull();
    expect(PendingUpload::find($recent->id))->not->toBeNull();
});
