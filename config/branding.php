<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primary Brand Color
    |--------------------------------------------------------------------------
    |
    | Set your primary brand color. This is used to automatically generate
    | gradients for the logo container, buttons, and page background.
    | Use Tailwind color names: purple, indigo, blue, emerald, rose, etc.
    |
    */

    'primary_color' => env('BRANDING_PRIMARY_COLOR', 'purple'),

    /*
    |--------------------------------------------------------------------------
    | Secondary Brand Color
    |--------------------------------------------------------------------------
    |
    | Set your secondary brand color for gradients.
    | Use Tailwind color names: purple, indigo, blue, emerald, rose, etc.
    |
    */

    'secondary_color' => env('BRANDING_SECONDARY_COLOR', 'indigo'),

    /*
    |--------------------------------------------------------------------------
    | Accent Color
    |--------------------------------------------------------------------------
    |
    | A third color used for background effects (the center glow).
    | Use Tailwind color names: purple, indigo, blue, fuchsia, emerald, etc.
    |
    */

    'accent_color' => env('BRANDING_ACCENT_COLOR', 'fuchsia'),

    /*
    |--------------------------------------------------------------------------
    | Logo Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the logo displayed in the application header.
    |
    | Type: 'svg' (inline SVG) or 'image' (external file)
    |
    | show_container: When true, displays the gradient container behind the logo.
    |                 When false, the logo is displayed without a container
    |                 (useful for custom logos that include their own styling).
    |
    */

    'logo' => [
        'type' => env('BRANDING_LOGO_TYPE', 'svg'),

        // Path to logo image (relative to public directory)
        // Used when type is 'image'
        // SECURITY NOTE: Path is sanitized to prevent directory traversal
        'image' => preg_replace('/\.\./', '', env('BRANDING_LOGO_IMAGE', '/logo.svg')),

        // Show the gradient container behind the logo
        // Set to false if your logo already includes its own background/container
        'show_container' => env('BRANDING_LOGO_SHOW_CONTAINER', true),

        // Default SVG icon (lock icon)
        // The SVG should NOT include size classes - sizing is handled by the container
        'svg' => '<svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo Sizes
    |--------------------------------------------------------------------------
    |
    | Tailwind CSS size classes for the logo container.
    |
    */

    'logo_size' => [
        'large' => 'w-14 h-14',   // Used on main pages
        'small' => 'w-10 h-10',   // Used in footer
    ],

];
