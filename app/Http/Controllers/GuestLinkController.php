<?php

namespace App\Http\Controllers;

use App\Models\GuestLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class GuestLinkController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $ttlHours = config('features.guest_link_ttl_hours', 24);
        $expiresAt = now()->addHours($ttlHours);

        $guestLink = GuestLink::create([
            'user_id' => $request->user()->id,
            'expires_at' => $expiresAt,
        ]);

        $signedUrl = URL::signedRoute('guest.access', [
            'guestLink' => $guestLink->id,
        ]);

        return response()->json([
            'id' => $guestLink->id,
            'url' => $signedUrl,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function access(Request $request, GuestLink $guestLink): \Illuminate\Http\RedirectResponse
    {
        if ($guestLink->isExpired()) {
            abort(410, 'This guest link has expired.');
        }

        $request->session()->put('guest_link_id', $guestLink->id);
        $request->session()->put('guest_link_expires_at', $guestLink->expires_at->toIso8601String());

        return redirect()->route('home');
    }
}
