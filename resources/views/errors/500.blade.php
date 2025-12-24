@php
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
@endphp

<x-layouts.error>
    <x-slot:code>500</x-slot:code>

    <x-slot:title>Server Error</x-slot:title>

    <x-slot:description>
        Something went wrong on our end. Don't worry, your secrets are safe. Please try again in a few moments.
    </x-slot:description>

    <x-slot:icon>
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
    </x-slot:icon>

    <x-slot:iconGradient>bg-gradient-to-br from-{{ $primary }}-500 to-{{ $secondary }}-600</x-slot:iconGradient>
    <x-slot:iconShadow>shadow-{{ $primary }}-500/30</x-slot:iconShadow>
    <x-slot:codeGradient>from-{{ $primary }}-400 via-{{ $secondary }}-400 to-{{ $primary }}-400</x-slot:codeGradient>

    <x-slot:extra>
        <div class="relative group max-w-sm mx-auto">
            <div class="absolute -inset-1 bg-gradient-to-r from-{{ $primary }}-600 via-{{ $secondary }}-600 to-{{ $primary }}-600 rounded-2xl opacity-30 blur-sm"></div>
            <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-4">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-8 h-8 rounded-lg bg-{{ $primary }}-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $primary }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-200 text-sm mb-0.5">We're on it</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Try refreshing or come back later.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:extra>

    <x-slot:actions>
        <button
            onclick="location.reload()"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-{{ $primary }}-600 to-{{ $secondary }}-600 hover:from-{{ $primary }}-500 hover:to-{{ $secondary }}-500 text-white font-medium rounded-xl shadow-lg shadow-{{ $primary }}-500/25 hover:shadow-{{ $primary }}-500/40 transition-all duration-200"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Try Again
        </button>

        <a
            href="{{ route('home') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl border border-slate-700 hover:border-slate-600 transition-all duration-200"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Go Home
        </a>
    </x-slot:actions>
</x-layouts.error>

