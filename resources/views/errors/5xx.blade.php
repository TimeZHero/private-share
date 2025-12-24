@php
    $code = (int) $exception->getStatusCode();
    $messages = [
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        504 => 'Gateway Timeout',
    ];
    $title = $messages[$code] ?? 'Server Error';
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
@endphp

<x-layouts.error>
    <x-slot:code>{{ $code }}</x-slot:code>

    <x-slot:title>{{ $title }}</x-slot:title>

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

