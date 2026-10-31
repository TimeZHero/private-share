<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\GuestLink;
use App\Models\PendingUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;

const GIGABYTE = 1024 * 1024 * 1024;

beforeEach(function () {
    Storage::fake(config('features.file_disk'));
    Feature::purge([Authentication::class, FileUploads::class]);
    config([
        'features.auth' => true,
        'features.file_uploads' => true,
        'features.file_max_size_gb' => 10,
        'features.file_daily_quota_gb' => 100,
    ]);
    $this->actingAs(User::factory()->create());
});

function declareUpload(int $size, ?int $totalChunks = null): TestResponse
{
    return test()->postJson('/api/files/initiate', [
        'name' => 'big.bin',
        'mime_type' => 'application/octet-stream',
        'size' => $size,
        'total_chunks' => $totalChunks ?? (int) ceil($size / PendingUpload::CHUNK_BYTES),
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ]);
}

function sendRawChunk(string $uploadId, int $index, int $bytes): TestResponse
{
    return test()->postJson("/api/files/{$uploadId}/chunk", [
        'chunk_index' => $index,
        'chunk' => UploadedFile::fake()->createWithContent('chunk', str_repeat("\0", $bytes)),
    ]);
}

describe('declared size', function () {
    test('a file of exactly the maximum size is accepted', function () {
        declareUpload(10 * GIGABYTE)->assertCreated();
    });

    test('a file over the maximum size is rejected', function () {
        declareUpload(10 * GIGABYTE + 1)->assertUnprocessable()->assertJsonValidationErrors('size');
    });

    test('the chunk count must match the declared size', function () {
        declareUpload(20 * 1024 * 1024, totalChunks: 1)->assertUnprocessable()->assertJsonValidationErrors('total_chunks');
        declareUpload(20 * 1024 * 1024, totalChunks: 4)->assertUnprocessable()->assertJsonValidationErrors('total_chunks');
        declareUpload(20 * 1024 * 1024, totalChunks: 3)->assertCreated();
    });
});

describe('chunk validation', function () {
    test('a chunk larger than one encrypted chunk is rejected', function () {
        $uploadId = declareUpload(20 * 1024 * 1024)->json('upload_id');

        sendRawChunk($uploadId, 0, PendingUpload::CHUNK_BYTES + PendingUpload::TAG_BYTES + 1)
            ->assertUnprocessable()
            ->assertJson(['error' => 'chunk_too_large']);

        expect(PendingUpload::find($uploadId)->received_chunks)->toBe(0);
    });

    test('chunks cannot add up to more than the declared size', function () {
        $uploadId = declareUpload(1000)->json('upload_id');

        sendRawChunk($uploadId, 0, 1000 + PendingUpload::TAG_BYTES + 1)
            ->assertUnprocessable()
            ->assertJson(['error' => 'chunk_too_large']);
    });

    test('a chunk of the expected size is accepted', function () {
        $uploadId = declareUpload(1000)->json('upload_id');

        sendRawChunk($uploadId, 0, 1000 + PendingUpload::TAG_BYTES)->assertSuccessful();
    });
});

describe('daily quota', function () {
    test('ten maximum-size uploads fit in a day, the eleventh does not', function () {
        foreach (range(1, 10) as $upload) {
            declareUpload(10 * GIGABYTE)->assertCreated();
        }

        $response = declareUpload(1)
            ->assertTooManyRequests()
            ->assertJson(['error' => 'upload_quota_exceeded']);

        expect($response->json('message'))->toStartWith('This upload would go over your daily upload limit of 100 GB. Try again in ');
    });

    test('the quota counts file sizes as users see them, without encryption overhead', function () {
        config(['features.file_daily_quota_gb' => 1]);

        declareUpload(GIGABYTE)->assertCreated();
        declareUpload(1)->assertTooManyRequests();
    });

    test('rejected uploads do not use quota', function () {
        config(['features.file_daily_quota_gb' => 1]);

        declareUpload(5 * GIGABYTE)->assertTooManyRequests();
        declareUpload(GIGABYTE - 1024 * 1024)->assertCreated();
    });

    test('the quota renews after 24 hours', function () {
        config(['features.file_daily_quota_gb' => 1]);
        declareUpload(GIGABYTE - 1024 * 1024)->assertCreated();
        declareUpload(1024 * 1024 + 1)->assertTooManyRequests();

        $this->travel(24)->hours();
        $this->travel(1)->minutes();

        declareUpload(1024 * 1024)->assertCreated();
    });

    test('each user and each guest link has its own quota', function () {
        config(['features.file_daily_quota_gb' => 1]);
        declareUpload(GIGABYTE - 1024 * 1024)->assertCreated();

        $this->actingAs(User::factory()->create());
        declareUpload(GIGABYTE - 1024 * 1024)->assertCreated();

        auth()->logout();
        $guestLink = GuestLink::factory()->create();
        $this->withSession([
            'guest_link_id' => $guestLink->id,
            'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
        ]);
        test()->postJson('/api/files/initiate', [
            'name' => 'guest.bin',
            'mime_type' => 'application/octet-stream',
            'size' => GIGABYTE - 1024 * 1024,
            'total_chunks' => (int) ceil((GIGABYTE - 1024 * 1024) / PendingUpload::CHUNK_BYTES),
            'encryption_salt' => base64_encode(random_bytes(16)),
            'client_iv' => base64_encode(random_bytes(12)),
        ], ['Referer' => 'http://localhost/'])->assertCreated();
    });

    test('a quota of zero disables the limit', function () {
        config(['features.file_daily_quota_gb' => 0]);

        foreach (range(1, 12) as $upload) {
            declareUpload(10 * GIGABYTE)->assertCreated();
        }
    });
});
