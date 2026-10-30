<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by Laravel's TrustProxies middleware outside the local environment
    | (local trusts every proxy, see bootstrap/app.php). Comma-separated IPs
    | or CIDRs of the reverse proxies in front of the app, or the special
    | values "PRIVATE_SUBNETS" (all private/loopback ranges) and "*" (every
    | address, which lets clients spoof their IP via X-Forwarded-For).
    |
    | Unset means no proxy is trusted and forwarded headers are ignored.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
