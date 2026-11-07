<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestLinkRequest;
use App\Models\GuestLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class GuestLinkController extends Controller
{
    public function store(StoreGuestLinkRequest $request): JsonResponse
    {
        $duration = $request->duration();

        $guestLink = GuestLink::create([
            'user_id' => $request->user()->id,
            'expires_at' => $duration->expiresAt(),
        ]);

        Log::info('Guest link created', [
            'guest_link_id' => $guestLink->id,
            'duration_days' => $duration->value,
        ]);

        return response()->json([
            'id' => $guestLink->id,
            'url' => URL::signedRoute('guest.access', ['guestLink' => $guestLink->id]),
            'expires_at' => $guestLink->expires_at->toIso8601String(),
            'duration_days' => $duration->value,
        ]);
    }
}
