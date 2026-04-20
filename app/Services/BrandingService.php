<?php

namespace App\Services;

/**
 * Resolves branding config into CSS custom properties injected server-side
 * to prevent a flash of default colors before React hydrates.
 *
 * Only handles the visually critical variables (surface, text, button).
 * Shade derivation for primary/secondary/accent is left to the JS runtime
 * since those affect smaller elements with no perceptible flash.
 */
class BrandingService
{
    private const PALETTE = [
        'slate' => [400 => '#94a3b8', 600 => '#475569', 950 => '#020617'],
        'gray' => [400 => '#9ca3af', 600 => '#4b5563', 950 => '#030712'],
        'zinc' => [400 => '#a1a1aa', 600 => '#52525b', 950 => '#09090b'],
        'neutral' => [400 => '#a3a3a3', 600 => '#525252', 950 => '#0a0a0a'],
        'stone' => [400 => '#a8a29e', 600 => '#57534e', 950 => '#0c0a09'],
        'red' => [400 => '#f87171', 600 => '#dc2626', 950 => '#450a0a'],
        'orange' => [400 => '#fb923c', 600 => '#ea580c', 950 => '#431407'],
        'amber' => [400 => '#fbbf24', 600 => '#d97706', 950 => '#451a03'],
        'yellow' => [400 => '#facc15', 600 => '#ca8a04', 950 => '#422006'],
        'lime' => [400 => '#a3e635', 600 => '#65a30d', 950 => '#1a2e05'],
        'green' => [400 => '#4ade80', 600 => '#16a34a', 950 => '#052e16'],
        'emerald' => [400 => '#34d399', 600 => '#059669', 950 => '#022c22'],
        'teal' => [400 => '#2dd4bf', 600 => '#0d9488', 950 => '#042f2e'],
        'cyan' => [400 => '#22d3ee', 600 => '#0891b2', 950 => '#083344'],
        'sky' => [400 => '#38bdf8', 600 => '#0284c7', 950 => '#082f49'],
        'blue' => [400 => '#60a5fa', 600 => '#2563eb', 950 => '#172554'],
        'indigo' => [400 => '#818cf8', 600 => '#4f46e5', 950 => '#1e1b4b'],
        'violet' => [400 => '#a78bfa', 600 => '#7c3aed', 950 => '#2e1065'],
        'purple' => [400 => '#c084fc', 600 => '#9333ea', 950 => '#3b0764'],
        'fuchsia' => [400 => '#e879f9', 600 => '#c026d3', 950 => '#4a044e'],
        'pink' => [400 => '#f472b6', 600 => '#db2777', 950 => '#500724'],
        'rose' => [400 => '#fb7185', 600 => '#e11d48', 950 => '#4c0519'],
    ];

    public static function cssVariables(): string
    {
        $surface = self::resolve(config('branding.background', '#0f172a'), 950, '#0f172a');
        $text = self::resolve(config('branding.foreground', '#e2e8f0'), 400, '#e2e8f0');
        $button = self::resolve(
            config('branding.action') ?: config('branding.primary', 'purple'),
            600,
            '#9333ea',
        );

        return implode('', [
            "--color-surface:{$surface};",
            "--color-surface-light:color-mix(in srgb,{$surface} 92%,white);",
            "--color-text:{$text};",
            "--color-button:{$button};",
            "--color-button-hover:color-mix(in srgb,{$button} 85%,white);",
        ]);
    }

    private static function resolve(string $color, int $shade, string $fallback): string
    {
        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $color)) {
            return str_starts_with($color, '#') ? $color : "#{$color}";
        }

        return self::PALETTE[$color][$shade] ?? $fallback;
    }
}
