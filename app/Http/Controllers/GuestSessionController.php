<?php

namespace App\Http\Controllers;

use App\Models\GuestLink;
use App\Services\GuestAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, GuestLink $guestLink, GuestAccess $guestAccess): RedirectResponse
    {
        $this->ensureUsable($guestLink);

        if (! $guestAccess->start($request, $guestLink)) {
            abort(410, 'This guest link has already been used.');
        }

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
