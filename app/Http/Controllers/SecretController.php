<?php

namespace App\Http\Controllers;

use App\Events\SecretRetrieved;
use App\Models\Secret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecretController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $hasFile = ! empty($request->input('shared_file_id'));

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:1048576'],
            'password' => $hasFile
                ? ['required', 'string', 'min:4', 'max:255']
                : ['sometimes', 'nullable', 'string', 'min:4', 'max:255'],
            'markdown_enabled' => ['sometimes', 'boolean'],
            'shared_file_id' => ['sometimes', 'nullable', 'string', 'exists:shared_files,id'],
        ]);

        if (empty($validated['content']) && empty($validated['shared_file_id'])) {
            return response()->json([
                'message' => 'Either content or a file attachment is required.',
                'errors' => ['content' => ['Either content or a file attachment is required.']],
            ], 422);
        }

        $secret = Secret::create($validated);

        return response()->json([
            'id' => $secret->id,
        ]);
    }

    /**
     * Check secret metadata (confirmation/password requirements) without retrieving content.
     */
    public function check(Secret $secret): JsonResponse
    {
        $response = [
            'requires_password' => $secret->isPasswordProtected(),
            'markdown_enabled' => $secret->markdown_enabled,
            'has_file' => $secret->hasFile(),
        ];

        if ($secret->hasFile()) {
            $response['file'] = [
                'original_name' => $secret->sharedFile->original_name,
                'size' => $secret->sharedFile->size,
                'formatted_size' => $secret->sharedFile->formattedSize(),
                'mime_type' => $secret->sharedFile->mime_type,
                'client_encrypted' => $secret->sharedFile->client_encrypted,
                'encryption_salt' => $secret->sharedFile->encryption_salt,
                'client_iv' => $secret->sharedFile->client_iv,
            ];
        }

        return response()->json($response);
    }

    /**
     * Retrieve the secret content after confirmation/password verification.
     */
    public function retrieve(Request $request, Secret $secret): JsonResponse
    {
        if ($secret->isPasswordProtected()) {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($request->password, $secret->password)) {
                return response()->json([
                    'error' => 'invalid_password',
                    'message' => 'The password is incorrect.',
                ], 403);
            }
        }

        $content = $secret->content;
        $createdAt = $secret->created_at->format('M j, Y \a\t g:i A');

        SecretRetrieved::dispatch($secret, $request->ip());

        $response = [
            'content' => $content,
            'created_at' => $createdAt,
            'markdown_enabled' => $secret->markdown_enabled,
            'has_file' => $secret->hasFile(),
        ];

        if ($secret->hasFile()) {
            $response['file_id'] = $secret->shared_file_id;
        }

        return response()->json($response);
    }
}
