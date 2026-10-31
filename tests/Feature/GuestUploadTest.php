<?php

use App\Features\Authentication;
use App\Features\FileUploads;
use App\Models\GuestLink;
use App\Models\PendingUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Storage::fake(config('features.file_disk'));
    Feature::purge([Authentication::class, FileUploads::class]);
    config(['features.auth' => true, 'features.file_uploads' => true]);
});

function asGuestOf(GuestLink $guestLink): array
{
    return [
        'guest_link_id' => $guestLink->id,
        'guest_link_expires_at' => $guestLink->expires_at->toIso8601String(),
    ];
}

function startUpload(): TestResponse
{
    return test()->postJson('/api/files/initiate', [
        'name' => 'guest.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'total_chunks' => 1,
        'encryption_salt' => base64_encode(random_bytes(16)),
        'client_iv' => base64_encode(random_bytes(12)),
    ], ['Referer' => 'http://localhost/']);
}

function sendChunk(string $uploadId): TestResponse
{
    return test()->postJson("/api/files/{$uploadId}/chunk", [
        'chunk_index' => 0,
        'chunk' => UploadedFile::fake()->createWithContent('chunk', random_bytes(1040)),
    ], ['Referer' => 'http://localhost/']);
}

test('guests see the file uploader', function () {
    $this->withSession(asGuestOf(GuestLink::factory()->create()))
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('fileUploadsEnabled', true));
});

test('a guest can upload a file end to end', function () {
    $guestLink = GuestLink::factory()->create();
    $this->withSession(asGuestOf($guestLink));

    $uploadId = startUpload()->assertCreated()->json('upload_id');
    sendChunk($uploadId)->assertSuccessful();
    $this->postJson("/api/files/{$uploadId}/complete", [], ['Referer' => 'http://localhost/'])->assertCreated();

    expect(PendingUpload::find($uploadId))->toBeNull();
});

test('a guest upload belongs to the guest link, not to a user', function () {
    $guestLink = GuestLink::factory()->create();
    $this->withSession(asGuestOf($guestLink));

    $upload = PendingUpload::find(startUpload()->json('upload_id'));

    expect($upload->guest_link_id)->toBe($guestLink->id)
        ->and($upload->user_id)->toBeNull();
});

test('another guest cannot touch a guest upload', function () {
    $this->withSession(asGuestOf(GuestLink::factory()->create()));
    $uploadId = startUpload()->json('upload_id');

    $this->withSession(asGuestOf(GuestLink::factory()->create()));

    sendChunk($uploadId)->assertNotFound();
});

test('a signed-in user cannot touch a guest upload', function () {
    $this->withSession(asGuestOf(GuestLink::factory()->create()));
    $uploadId = startUpload()->json('upload_id');

    $this->flushSession();
    $this->actingAs(User::factory()->create());

    sendChunk($uploadId)->assertNotFound();
});

test('a guest cannot touch a user upload', function () {
    $this->actingAs(User::factory()->create());
    $uploadId = startUpload()->json('upload_id');

    auth()->logout();
    $this->withSession(asGuestOf(GuestLink::factory()->create()));

    sendChunk($uploadId)->assertNotFound();
});

test('a guest whose access expired cannot upload', function () {
    $this->withSession([
        'guest_link_id' => 'expired-link',
        'guest_link_expires_at' => now()->subMinute()->toIso8601String(),
    ]);

    startUpload()->assertUnauthorized();
});

test('anonymous visitors still cannot upload', function () {
    startUpload()->assertUnauthorized();
});

test('guests cannot create guest links of their own', function () {
    $this->withSession(asGuestOf(GuestLink::factory()->create()))
        ->postJson('/api/guest-links', [], ['Referer' => 'http://localhost/'])
        ->assertUnauthorized();
});

test('guests are rate limited per guest link, not per shared IP', function () {
    $officeIp = ['REMOTE_ADDR' => '93.184.216.34'];

    $this->withSession(asGuestOf(GuestLink::factory()->create()))->withServerVariables($officeIp);
    foreach (range(1, 30) as $attempt) {
        startUpload()->assertCreated();
    }
    startUpload()->assertTooManyRequests();

    $this->withSession(asGuestOf(GuestLink::factory()->create()))->withServerVariables($officeIp);
    startUpload()->assertCreated();
});
