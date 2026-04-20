<?php

namespace App\Http\Controllers;

use App\Models\PendingUpload;
use App\Models\SharedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SharedFileController extends Controller
{
    /**
     * Step 1: Initiate a chunked upload.
     *
     * The client declares the file metadata and how many chunks it will send.
     * Returns an upload_id the client uses for subsequent chunk uploads.
     */
    public function initiate(Request $request): JsonResponse
    {
        $maxGb = config('features.file_max_size_gb', 10);
        $maxBytes = $maxGb * 1024 * 1024 * 1024;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:127'],
            'size' => ['required', 'integer', 'min:1', "max:{$maxBytes}"],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'encryption_salt' => ['required', 'string', 'max:64'],
            'client_iv' => ['required', 'string', 'max:24'],
        ]);

        $uploadId = (string) Str::uuid();
        $tempPath = storage_path("app/private/uploads/{$uploadId}");

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $pendingUpload = PendingUpload::create([
            'id' => $uploadId,
            'original_name' => $request->input('name'),
            'mime_type' => $request->input('mime_type'),
            'total_size' => $request->integer('size'),
            'total_chunks' => $request->integer('total_chunks'),
            'temp_path' => $tempPath,
            'encryption_salt' => $request->input('encryption_salt'),
            'client_iv' => $request->input('client_iv'),
        ]);

        return response()->json([
            'upload_id' => $pendingUpload->id,
        ], 201);
    }

    /**
     * Step 2: Receive a single chunk of the file.
     *
     * Chunks are appended to a temp file on disk in order. Each request
     * carries at most CHUNK_SIZE bytes, so post_max_size stays small.
     */
    public function chunk(Request $request, PendingUpload $pendingUpload): JsonResponse
    {
        $request->validate([
            'chunk_index' => ['required', 'integer', 'min:0'],
            'chunk' => ['required', 'file'],
        ]);

        $chunkIndex = $request->integer('chunk_index');
        $expectedIndex = $pendingUpload->received_chunks;

        if ($chunkIndex !== $expectedIndex) {
            return response()->json([
                'error' => 'chunk_out_of_order',
                'message' => "Expected chunk {$expectedIndex}, got {$chunkIndex}.",
                'expected' => $expectedIndex,
            ], 409);
        }

        $chunkFile = $request->file('chunk');
        $chunkData = file_get_contents($chunkFile->getRealPath());

        file_put_contents($pendingUpload->temp_path, $chunkData, FILE_APPEND | LOCK_EX);

        $pendingUpload->increment('received_chunks');

        return response()->json([
            'received_chunks' => $pendingUpload->received_chunks,
            'complete' => $pendingUpload->isComplete(),
        ]);
    }

    /**
     * Step 3: Finalize the upload.
     *
     * All chunks have been received. The assembled temp file already contains
     * client-side encrypted data (AES-256-GCM chunks) — we store it as-is.
     * No server-side re-encryption: the server never sees plaintext.
     */
    public function complete(PendingUpload $pendingUpload): JsonResponse
    {
        if (! $pendingUpload->isComplete()) {
            return response()->json([
                'error' => 'upload_incomplete',
                'message' => "Only {$pendingUpload->received_chunks} of {$pendingUpload->total_chunks} chunks received.",
            ], 409);
        }

        $assembledSize = file_exists($pendingUpload->temp_path) ? filesize($pendingUpload->temp_path) : 0;
        $isClientEncrypted = $pendingUpload->encryption_salt && $pendingUpload->client_iv;
        $expectedSize = $isClientEncrypted
            ? $pendingUpload->total_size + ($pendingUpload->total_chunks * 16)
            : $pendingUpload->total_size;

        if ($assembledSize !== $expectedSize) {
            @unlink($pendingUpload->temp_path);
            $pendingUpload->delete();

            return response()->json([
                'error' => 'size_mismatch',
                'message' => 'Assembled file size does not match the expected size.',
            ], 422);
        }

        $storagePath = 'shared-files/'.Str::uuid().'.enc';

        $inputStream = fopen($pendingUpload->temp_path, 'rb');
        if ($inputStream === false) {
            throw new RuntimeException('Could not open assembled temp file.');
        }

        try {
            Storage::disk(config('features.file_disk'))->writeStream($storagePath, $inputStream);
        } finally {
            if (is_resource($inputStream)) {
                fclose($inputStream);
            }
        }

        $sharedFile = SharedFile::create([
            'original_name' => $pendingUpload->original_name,
            'mime_type' => $pendingUpload->mime_type,
            'size' => $pendingUpload->total_size,
            'storage_path' => $storagePath,
            'encryption_salt' => $pendingUpload->encryption_salt,
            'client_iv' => $pendingUpload->client_iv,
            'client_encrypted' => true,
        ]);

        @unlink($pendingUpload->temp_path);
        $pendingUpload->delete();

        return response()->json([
            'shared_file_id' => $sharedFile->id,
        ], 201);
    }

    /**
     * Serve the client-encrypted file to the browser, then burn.
     *
     * Uses BinaryFileResponse for the local disk (proper Content-Length,
     * no Transfer-Encoding: chunked, supports range requests). The file
     * on disk is already client-side encrypted — the server just passes
     * bytes through without touching the content.
     */
    public function download(SharedFile $sharedFile): BinaryFileResponse|JsonResponse
    {
        $disk = Storage::disk(config('features.file_disk'));

        if (! $disk->exists($sharedFile->storage_path)) {
            return response()->json([
                'error' => 'file_not_found',
                'message' => 'The file could not be found or retrieved from storage.',
            ], 404);
        }

        $fullPath = $disk->path($sharedFile->storage_path);

        $storagePath = $sharedFile->storage_path;
        $sharedFileToDelete = $sharedFile;
        app()->terminating(function () use ($sharedFileToDelete, $disk, $storagePath): void {
            $disk->delete($storagePath);
            $sharedFileToDelete->delete();
        });

        return response()->file($fullPath, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.addslashes($sharedFile->original_name).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Client-Encrypted' => $sharedFile->client_encrypted ? '1' : '0',
            'X-Encryption-Salt' => $sharedFile->encryption_salt ?? '',
            'X-Client-Iv' => $sharedFile->client_iv ?? '',
            'X-Original-Mime-Type' => $sharedFile->mime_type,
            'X-Plaintext-Size' => (string) $sharedFile->size,
            'Access-Control-Expose-Headers' => 'X-Client-Encrypted, X-Encryption-Salt, X-Client-Iv, X-Original-Mime-Type, X-Plaintext-Size, Content-Disposition',
        ]);
    }
}
