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
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'requires_confirmation' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'nullable', 'string', 'min:4'],
        ]);

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
        return response()->json([
            'requires_confirmation' => $secret->requires_confirmation,
            'requires_password' => $secret->isPasswordProtected(),
        ]);
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

        // Dispatch event to log retrieval and burn the secret
        SecretRetrieved::dispatch($secret, $request->ip());

        return response()->json([
            'content' => $secret->content,
            'created_at' => $secret->created_at->format('M j, Y \a\t g:i A'),
        ]);
    }
}
