<?php

namespace App\Http\Controllers;

use App\Models\Secret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $secret = Secret::create($validated);

        return response()->json([
            'id' => $secret->id,
        ]);
    }
}
