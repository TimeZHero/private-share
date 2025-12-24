@php
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
@endphp

<x-layouts.error>
    <x-slot:code>503</x-slot:code>

    <x-slot:title>Under Maintenance</x-slot:title>

    <x-slot:description>
        We're currently performing scheduled maintenance to improve your experience. We'll be back online shortly.
    </x-slot:description>

    <x-slot:icon>
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-200 text-sm mb-0.5">Be right back</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Your secrets are safe. We'll be back shortly.
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
            Check Again
        </button>
    </x-slot:actions>
</x-layouts.error>

