<?php

namespace App\Http\Controllers;

use App\Models\GuestLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Opening a guest link only shows a confirmation; the link is consumed by
 * the button press, so mail scanners that prefetch links cannot burn it.
 */
class GuestSessionController extends Controller
{
    public function create(Request $request, GuestLink $guestLink): Response|RedirectResponse
    {
        if ($request->session()->get('guest_link_id') === $guestLink->id) {
            return redirect()->route('home');
        }

        $this->ensureUsable($guestLink);

        return Inertia::render('GuestAccess', [
            'acceptUrl' => $request->getRequestUri(),
            'expiresAt' => $guestLink->expires_at->toIso8601String(),
        ]);
    }

    public function store(Request $request, GuestLink $guestLink): RedirectResponse
    {
        $this->ensureUsable($guestLink);

        if (! $guestLink->consume()) {
            abort(410, 'This guest link has already been used.');
        }

        $request->session()->regenerate();
        $request->session()->put('guest_link_id', $guestLink->id);
        $request->session()->put('guest_link_expires_at', $guestLink->expires_at->toIso8601String());

        Log::info('Guest session started', ['guest_link_id' => $guestLink->id]);

        return redirect()->route('home');
    }

    private function ensureUsable(GuestLink $guestLink): void
    {
        if ($guestLink->isExpired()) {
            abort(410, 'This guest link has expired.');
        }

        if ($guestLink->isUsed()) {
            abort(410, 'This guest link has already been used.');
        }
    }
}
