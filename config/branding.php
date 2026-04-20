<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand Colors
    |--------------------------------------------------------------------------
    |
    | Colors can be Tailwind names (purple, indigo, blue, emerald, rose...)
    | OR hex values (e.g. "#7C3AED", "7C3AED", "#EBFE50").
    | Shade variants (lighter/darker) are derived automatically from hex.
    |
    */

    'primary' => env('BRANDING_PRIMARY', 'purple'),
    'secondary' => env('BRANDING_SECONDARY', 'indigo'),
    'accent' => env('BRANDING_ACCENT', 'fuchsia'),

    /*
    |--------------------------------------------------------------------------
    | Action Color
    |--------------------------------------------------------------------------
    |
    | Color for interactive elements (buttons, toggles, CTAs).
    | Defaults to the primary color if not set.
    |
    */

    'action' => env('BRANDING_ACTION'),

    /*
    |--------------------------------------------------------------------------
    | Background & Foreground Colors
    |--------------------------------------------------------------------------
    |
    | background: dark background for header, cards, inputs, body.
    | foreground: main text / content color.
    |
    | Accept Tailwind names or hex values, like the brand colors above.
    | - Tailwind name for background → uses the darkest shade (950)
    | - Tailwind name for foreground → uses shade 400 (readable on dark bg)
    |
    */

    'background' => env('BRANDING_BACKGROUND', '#0f172a'),
    'foreground' => env('BRANDING_FOREGROUND', '#e2e8f0'),

    /*
    |--------------------------------------------------------------------------
    | Logo Configuration
    |--------------------------------------------------------------------------
    |
    | Set BRANDING_LOGO_IMAGE to a filename in the public/ directory
    | (e.g. "my-logo.svg") to use a custom logo. Leave empty to use
    | the built-in lock icon.
    |
    | show_container: When true, displays the gradient container behind the logo.
    |                 When false, the logo is displayed without a container
    |                 (useful for custom logos that include their own styling).
    |
    */

    'logo' => [
        // Filename in public/ — auto-prefixed with "/" for absolute URL resolution
        'image' => (function () {
            $raw = preg_replace('/\.\./', '', env('BRANDING_LOGO_IMAGE', ''));

            return $raw ? '/'.ltrim($raw, '/') : null;
        })(),

        'show_container' => env('BRANDING_LOGO_SHOW_CONTAINER', true),

        // Built-in SVG fallback (used when image is not set)
        'svg' => '<svg class="w-full h-full" style="color:var(--color-primary-contrast)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>',
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
