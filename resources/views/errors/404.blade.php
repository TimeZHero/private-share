@php
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
@endphp

<x-layouts.error>
    <x-slot:code>404</x-slot:code>

    <x-slot:title>Not found</x-slot:title>

    <x-slot:description>
        The page you're looking for doesn't exist or has been moved.
    </x-slot:description>

    <x-slot:icon>
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </x-slot:icon>

    <x-slot:iconGradient>bg-gradient-to-br from-{{ $primary }}-500 to-{{ $secondary }}-600</x-slot:iconGradient>
    <x-slot:iconShadow>shadow-{{ $primary }}-500/30</x-slot:iconShadow>
    <x-slot:codeGradient>from-{{ $primary }}-400 via-{{ $secondary }}-400 to-{{ $primary }}-400</x-slot:codeGradient>

    <x-slot:actions>
        <a
            href="{{ route('home') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-{{ $primary }}-600 to-{{ $secondary }}-600 hover:from-{{ $primary }}-500 hover:to-{{ $secondary }}-500 text-white font-medium rounded-xl shadow-lg shadow-{{ $primary }}-500/25 hover:shadow-{{ $primary }}-500/40 transition-all duration-200"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Go Home
        </a>
    </x-slot:actions>

    <x-slot:extra>
        <div class="relative group max-w-sm mx-auto">
            <div class="absolute -inset-1 bg-gradient-to-r from-{{ $primary }}-600 via-{{ $secondary }}-600 to-{{ $primary }}-600 rounded-2xl opacity-30 blur-sm"></div>
            <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-4">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-8 h-8 rounded-lg bg-{{ $primary }}-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $primary }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-200 text-sm mb-0.5">Looking for a secret?</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Secrets are deleted after viewing. Ask the sender for a new link.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:extra>
</x-layouts.error>

