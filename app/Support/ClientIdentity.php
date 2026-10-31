<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ClientIdentity
{
    /**
     * Key for per-client limits: the account when signed in, the guest link for
     * guest sessions, otherwise the network.
     */
    public static function of(Request $request): string
    {
        $user = $request->user();

        if ($user !== null) {
            return "user:{$user->getAuthIdentifier()}";
        }

        $guestLinkId = $request->hasSession() ? $request->session()->get('guest_link_id') : null;

        if ($guestLinkId !== null) {
            return "guest:{$guestLinkId}";
        }

        return 'ip:'.self::network((string) $request->ip());
    }

    /**
     * IPv6 clients usually control a whole /64, so keying on single addresses
     * would hand out unlimited fresh identities.
     */
    private static function network(string $ip): string
    {
        $packed = inet_pton($ip);

        if ($packed === false || strlen($packed) === 4) {
            return $ip;
        }

        return inet_ntop(substr($packed, 0, 8).str_repeat("\0", 8));
    }
}
