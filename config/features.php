<?php

return [

    'auth' => env('FEATURE_AUTH', false),

    'file_uploads' => env('FEATURE_FILE_UPLOADS', false),

    'file_disk' => env('FILE_DISK', 'local'),

    'file_max_size_gb' => (int) env('FILE_MAX_SIZE_GB', 10),

    'guest_link_ttl_hours' => (int) env('GUEST_LINK_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Allowed Email Patterns
    |--------------------------------------------------------------------------
    |
    | An array of regex patterns that an OAuth user's email must match against
    | to be allowed to log in. If empty, all emails are allowed.
    |
    | Examples:
    |   - "/@example\.com$/i"            → only @example.com domain
    |   - "/@(example|agency)\.com$/i"   → multiple domains
    |   - "/^admin@example\.com$/i"      → exact email match
    |
    | Set via AUTH_ALLOWED_EMAILS as a comma-separated list of patterns.
    |
    */
    'allowed_email_patterns' => array_filter(
        array_map('trim', explode(',', env('AUTH_ALLOWED_EMAILS', ''))),
    ),

];
