<?php

return [

    'auth' => env('FEATURE_AUTH', false),

    'file_uploads' => env('FEATURE_FILE_UPLOADS', false),

    'file_disk' => env('FILE_DISK', 'local'),

    'file_max_size_gb' => (int) env('FILE_MAX_SIZE_GB', 10),

    // File size each user or guest link may upload per 24 hours (as users see it, without the
    // ~0.0002% encryption overhead, so 100 GB fits ten 10 GB files). 0 disables it.
    'file_daily_quota_gb' => (int) env('FILE_DAILY_QUOTA_GB', 100),

    /*
    |--------------------------------------------------------------------------
    | Allowed Email Domains
    |--------------------------------------------------------------------------
    |
    | Google Workspace domains whose accounts may sign in, matched exactly
    | (no subdomains, no patterns). Comma-separated in AUTH_ALLOWED_DOMAINS,
    | e.g. "example.com,partner.io". Empty means nobody can sign in.
    |
    */
    'allowed_email_domains' => array_values(array_filter(array_map(
        static fn (string $domain): string => strtolower(ltrim(trim($domain), '@')),
        explode(',', (string) env('AUTH_ALLOWED_DOMAINS', '')),
    ))),

    // The old regex setting must be removed: sign-in stays closed while it is set.
    'legacy_allowed_email_patterns' => filled(env('AUTH_ALLOWED_EMAILS')),

];
