@php
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
    $accent = config('branding.accent_color');
    $showContainer = config('branding.logo.show_container');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Error' }} - {{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|jetbrains-mono:400,500" rel="stylesheet" />

        @vite(['resources/css/app.css'])

        <style>
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }
            .mono {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
            }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-900 via-{{ $primary }}-950 to-slate-900 text-white antialiased">
        <!-- Decorative background blurs -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-20 w-96 h-96 bg-{{ $primary }}-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-{{ $secondary }}-600/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-{{ $accent }}-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative min-h-screen flex flex-col items-center justify-center p-6">
            <div class="w-full max-w-lg">
                <div class="text-center mb-8">
                    <!-- Icon slot -->
                    <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-2xl {{ $iconGradient ?? 'bg-gradient-to-br from-'.$primary.'-500 to-'.$secondary.'-600' }} shadow-lg {{ $iconShadow ?? 'shadow-'.$primary.'-500/30' }}">
                        {{ $icon }}
                    </div>

                    <!-- Error code -->
                    <div class="text-7xl font-bold mb-4 bg-gradient-to-r {{ $codeGradient ?? 'from-'.$primary.'-400 via-'.$secondary.'-400 to-'.$primary.'-400' }} bg-clip-text text-transparent">
                        {{ $code ?? '500' }}
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl font-semibold tracking-tight mb-3">
                        {{ $title ?? 'Something went wrong' }}
                    </h1>

                    <!-- Description -->
                    <p class="text-slate-400 max-w-md mx-auto">
                        {{ $description ?? 'An unexpected error occurred. Please try again later.' }}
                    </p>
                </div>

                <!-- Optional extra content -->
                @if(isset($extra))
                    <div class="mb-8">
                        {{ $extra }}
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    @if(isset($actions) && !$actions->isEmpty())
                        {{ $actions }}
                    @else
                        <a
                            href="{{ route('home') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-{{ $primary }}-600 to-{{ $secondary }}-600 hover:from-{{ $primary }}-500 hover:to-{{ $secondary }}-500 text-white font-medium rounded-xl shadow-lg shadow-{{ $primary }}-500/25 hover:shadow-{{ $primary }}-500/40 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Go Home
                        </a>

                        <button
                            onclick="history.back()"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl border border-slate-700 hover:border-slate-600 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Go Back
                        </button>
                    @endif
                </div>

                <!-- Footer -->
                <div class="mt-12 pt-6 border-t border-slate-800">
                    <div class="flex items-center justify-center text-sm text-slate-500">
                        <a href="{{ route('home') }}" class="flex items-center gap-2 hover:text-slate-300 transition-colors">
                            @php
                                // Normalize and sanitize logo path (prevent directory traversal)
                                $logoPath = config('branding.logo.image');
                                if ($logoPath) {
                                    $logoPath = str_replace(['..', '\\'], '', $logoPath);
                                    if (!str_starts_with($logoPath, '/') && !str_starts_with($logoPath, 'http')) {
                                        $logoPath = '/' . $logoPath;
                                    }
                                }
                            @endphp
                            @if($showContainer)
                                <div class="inline-flex items-center justify-center {{ config('branding.logo_size.small') }} p-2 rounded-xl bg-gradient-to-br from-{{ $primary }}-500 to-{{ $secondary }}-600 shadow-md shadow-{{ $primary }}-500/20">
                                    @if(config('branding.logo.type') === 'image')
                                        <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" class="w-full h-full object-contain">
                                    @else
                                        {!! config('branding.logo.svg') !!}
                                    @endif
                                </div>
                            @else
                                @if(config('branding.logo.type') === 'image')
                                    <img src="{{ $logoPath }}" alt="" class="h-8 w-auto">
                                @else
                                    <div class="{{ config('branding.logo_size.small') }}">
                                        {!! config('branding.logo.svg') !!}
                                    </div>
                                @endif
                            @endif
                            <span>{{ config('app.name') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
