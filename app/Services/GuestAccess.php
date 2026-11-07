<?php

namespace App\Services;

use App\Models\GuestLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * A guest's access lives in the session and in an encrypted cookie that
 * expires with the link, so it survives the session's idle timeout.
 */
final class GuestAccess
{
    public const COOKIE = 'guest_access';

    /**
     * Consumes the link and grants access to this browser. False when the
     * link was already used or expired in the meantime.
     */
    public function start(Request $request, GuestLink $guestLink): bool
    {
        $accessToken = Str::random(64);

        if (! $guestLink->consume(self::hash($accessToken))) {
            return false;
        }

        $this->grant($request, $guestLink);
        Cookie::queue($this->cookie("{$guestLink->id}|{$accessToken}", $guestLink->expires_at->getTimestamp()));

        Log::info('Guest session started', ['guest_link_id' => $guestLink->id]);

        return true;
    }

    /**
     * Makes the session's guest grant match the database: an expired or
     * revoked link ends it, and a missing grant is restored from the cookie.
     */
    public function resolve(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        if ($request->user() !== null) {
            return;
        }

        $guestLinkId = $request->session()->get('guest_link_id');

        if ($guestLinkId !== null) {
            $this->confirm($request, $guestLinkId);

            return;
        }

        $this->restore($request);
    }

    public function isGuest(Request $request): bool
    {
        return $request->hasSession() && $request->session()->has('guest_link_id');
    }

    public function end(Request $request, string $reason): void
    {
        Log::info('Guest session ended', [
            'guest_link_id' => $request->session()->get('guest_link_id'),
            'reason' => $reason,
        ]);

        $request->session()->forget(['guest_link_id', 'guest_link_expires_at']);
        Context::add('guest_link_id', null);
        $this->forgetCookie();
    }

    private function confirm(Request $request, string $guestLinkId): void
    {
        $guestLink = GuestLink::query()->active()->find($guestLinkId);

        if ($guestLink === null) {
            $this->end($request, 'expired_or_revoked');

            return;
        }

        $request->session()->put('guest_link_expires_at', $guestLink->expires_at->toIso8601String());
    }

    private function restore(Request $request): void
    {
        $cookieValue = $request->cookies->get(self::COOKIE);

        if (! is_string($cookieValue) || $cookieValue === '') {
            return;
        }

        $guestLink = $this->fromCookie($cookieValue);

        if ($guestLink === null) {
            Log::warning('Guest access cookie rejected');
            $this->forgetCookie();

            return;
        }

        $this->grant($request, $guestLink);

        Log::info('Guest session restored', ['guest_link_id' => $guestLink->id]);
    }

    private function fromCookie(string $cookieValue): ?GuestLink
    {
        $parts = explode('|', $cookieValue, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$guestLinkId, $accessToken] = $parts;
        $guestLink = GuestLink::query()->active()->find($guestLinkId);

        if ($guestLink?->access_token_hash === null) {
            return null;
        }

        if (! hash_equals($guestLink->access_token_hash, self::hash($accessToken))) {
            return null;
        }

        return $guestLink;
    }

    private function forgetCookie(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE, config('session.path'), config('session.domain')));
    }

    private function grant(Request $request, GuestLink $guestLink): void
    {
        $request->session()->regenerate();
        $request->session()->put('guest_link_id', $guestLink->id);
        $request->session()->put('guest_link_expires_at', $guestLink->expires_at->toIso8601String());
        Context::add('guest_link_id', $guestLink->id);
    }

    private function cookie(string $value, int $expiresAt): SymfonyCookie
    {
        return SymfonyCookie::create(
            name: self::COOKIE,
            value: $value,
            expire: $expiresAt,
            path: config('session.path'),
            domain: config('session.domain'),
            secure: config('session.secure'),
            httpOnly: true,
            sameSite: SymfonyCookie::SAMESITE_LAX,
        );
    }

    private static function hash(string $accessToken): string
    {
        return hash('sha256', $accessToken);
    }
}
