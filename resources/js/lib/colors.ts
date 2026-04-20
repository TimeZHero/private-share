/**
 * Tailwind CSS color palette (subset of shades used by branding).
 */
const palette: Record<string, Record<number, string>> = {
    slate: { 400: '#94a3b8', 500: '#64748b', 600: '#475569', 950: '#020617' },
    gray: { 400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 950: '#030712' },
    zinc: { 400: '#a1a1aa', 500: '#71717a', 600: '#52525b', 950: '#09090b' },
    neutral: { 400: '#a3a3a3', 500: '#737373', 600: '#525252', 950: '#0a0a0a' },
    stone: { 400: '#a8a29e', 500: '#78716c', 600: '#57534e', 950: '#0c0a09' },
    red: { 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 950: '#450a0a' },
    orange: { 400: '#fb923c', 500: '#f97316', 600: '#ea580c', 950: '#431407' },
    amber: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 950: '#451a03' },
    yellow: { 400: '#facc15', 500: '#eab308', 600: '#ca8a04', 950: '#422006' },
    lime: { 400: '#a3e635', 500: '#84cc16', 600: '#65a30d', 950: '#1a2e05' },
    green: { 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 950: '#052e16' },
    emerald: { 400: '#34d399', 500: '#10b981', 600: '#059669', 950: '#022c22' },
    teal: { 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 950: '#042f2e' },
    cyan: { 400: '#22d3ee', 500: '#06b6d4', 600: '#0891b2', 950: '#083344' },
    sky: { 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 950: '#082f49' },
    blue: { 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 950: '#172554' },
    indigo: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 950: '#1e1b4b' },
    violet: { 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 950: '#2e1065' },
    purple: { 400: '#c084fc', 500: '#a855f7', 600: '#9333ea', 950: '#3b0764' },
    fuchsia: { 400: '#e879f9', 500: '#d946ef', 600: '#c026d3', 950: '#4a044e' },
    pink: { 400: '#f472b6', 500: '#ec4899', 600: '#db2777', 950: '#500724' },
    rose: { 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 950: '#4c0519' },
};

function hexToHsl(hex: string): [number, number, number] {
    const red = parseInt(hex.slice(1, 3), 16) / 255;
    const green = parseInt(hex.slice(3, 5), 16) / 255;
    const blue = parseInt(hex.slice(5, 7), 16) / 255;

    const max = Math.max(red, green, blue);
    const min = Math.min(red, green, blue);
    const lightness = (max + min) / 2;
    let hue = 0;
    let saturation = 0;

    if (max !== min) {
        const delta = max - min;
        saturation = lightness > 0.5 ? delta / (2 - max - min) : delta / (max + min);
        if (max === red) hue = ((green - blue) / delta + (green < blue ? 6 : 0)) / 6;
        else if (max === green) hue = ((blue - red) / delta + 2) / 6;
        else hue = ((red - green) / delta + 4) / 6;
    }

    return [hue * 360, saturation * 100, lightness * 100];
}

function hslToHex(hue: number, saturation: number, lightness: number): string {
    const sat = saturation / 100;
    const lgt = lightness / 100;
    const chroma = (1 - Math.abs(2 * lgt - 1)) * sat;
    const x = chroma * (1 - Math.abs(((hue / 60) % 2) - 1));
    const match = lgt - chroma / 2;

    let red = 0, green = 0, blue = 0;
    if (hue < 60) { red = chroma; green = x; }
    else if (hue < 120) { red = x; green = chroma; }
    else if (hue < 180) { green = chroma; blue = x; }
    else if (hue < 240) { green = x; blue = chroma; }
    else if (hue < 300) { red = x; blue = chroma; }
    else { red = chroma; blue = x; }

    const toHex = (value: number) => Math.round((value + match) * 255).toString(16).padStart(2, '0');
    return `#${toHex(red)}${toHex(green)}${toHex(blue)}`;
}

function deriveShade(baseHex: string, targetLightness: number): string {
    const [hue, saturation] = hexToHsl(baseHex);
    return hslToHex(hue, saturation, targetLightness);
}

/**
 * Lighten a hex color by mixing it with white (avoids HSL hue shifts).
 * amount: 0 = original, 1 = pure white.
 */
function lighten(hex: string, amount: number): string {
    const red = parseInt(hex.slice(1, 3), 16);
    const green = parseInt(hex.slice(3, 5), 16);
    const blue = parseInt(hex.slice(5, 7), 16);
    const mix = (channel: number) => Math.round(channel + (255 - channel) * amount);
    const toHex = (value: number) => value.toString(16).padStart(2, '0');
    return `#${toHex(mix(red))}${toHex(mix(green))}${toHex(mix(blue))}`;
}

/**
 * Compute relative luminance (WCAG formula) to determine if a color is "bright".
 */
function luminance(hex: string): number {
    const toLinear = (value: number) => {
        const srgb = value / 255;
        return srgb <= 0.04045 ? srgb / 12.92 : ((srgb + 0.055) / 1.055) ** 2.4;
    };
    const red = toLinear(parseInt(hex.slice(1, 3), 16));
    const green = toLinear(parseInt(hex.slice(3, 5), 16));
    const blue = toLinear(parseInt(hex.slice(5, 7), 16));
    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

function isHex(value: string): boolean {
    return /^#?[0-9a-fA-F]{6}$/.test(value);
}

function normalizeHex(value: string): string {
    return value.startsWith('#') ? value : `#${value}`;
}

interface ColorShades {
    400: string;
    500: string;
    600: string;
    950: string;
}

function resolveShades(color: string, fallback: ColorShades): ColorShades {
    if (isHex(color)) {
        const hex = normalizeHex(color);
        return {
            400: deriveShade(hex, 65),
            500: hex,
            600: deriveShade(hex, 45),
            950: deriveShade(hex, 8),
        };
    }
    const entry = palette[color];
    if (entry) {
        return { 400: entry[400], 500: entry[500], 600: entry[600], 950: entry[950] };
    }
    return fallback;
}

const defaultPrimary: ColorShades = { 400: '#c084fc', 500: '#a855f7', 600: '#9333ea', 950: '#3b0764' };
const defaultSecondary: ColorShades = { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 950: '#1e1b4b' };
const defaultAccent: ColorShades = { 400: '#e879f9', 500: '#d946ef', 600: '#c026d3', 950: '#4a044e' };

/**
 * Resolve a color value to hex. Accepts:
 *   - Hex values: "#7C3AED", "7C3AED", "#EBFE50"
 *   - Tailwind names: "purple", "indigo", "red"
 *
 * For Tailwind names, the shade parameter determines which shade to pick.
 */
function resolveHex(color: string, shade: number, fallback: string): string {
    if (isHex(color)) return normalizeHex(color);
    return palette[color]?.[shade] ?? fallback;
}

export function applyBrandingColors(
    primary: string,
    secondary: string,
    accent: string,
    background: string,
    foreground: string,
    action?: string | null,
): void {
    const root = document.documentElement;
    const primaryShades = resolveShades(primary, defaultPrimary);
    const secondaryShades = resolveShades(secondary, defaultSecondary);
    const accentShades = resolveShades(accent, defaultAccent);

    root.style.setProperty('--color-primary-400', primaryShades[400]);
    root.style.setProperty('--color-primary-500', primaryShades[500]);
    root.style.setProperty('--color-primary-600', primaryShades[600]);
    root.style.setProperty('--color-primary-950', primaryShades[950]);
    root.style.setProperty('--color-secondary-400', secondaryShades[400]);
    root.style.setProperty('--color-secondary-500', secondaryShades[500]);
    root.style.setProperty('--color-secondary-600', secondaryShades[600]);
    root.style.setProperty('--color-accent-600', accentShades[600]);

    const surfaceHex = resolveHex(background, 950, '#0f172a');
    root.style.setProperty('--color-surface', surfaceHex);
    root.style.setProperty('--color-surface-light', lighten(surfaceHex, 0.08));

    root.style.setProperty('--color-text', resolveHex(foreground, 400, '#e2e8f0'));

    root.style.setProperty('--color-primary-contrast', luminance(primaryShades[500]) > 0.35 ? '#1a1a1a' : '#ffffff');

    const actionBase = action ? resolveHex(action, 600, primaryShades[600]) : primaryShades[600];
    const actionHover = action ? lighten(actionBase, 0.15) : primaryShades[500];
    const actionContrast = luminance(actionBase) > 0.35 ? '#1a1a1a' : '#ffffff';
    root.style.setProperty('--color-button', actionBase);
    root.style.setProperty('--color-button-hover', actionHover);
    root.style.setProperty('--color-button-contrast', actionContrast);
}
