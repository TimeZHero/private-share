@php
    $code = (int) $exception->getStatusCode();
    $messages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        405 => 'Method Not Allowed',
        408 => 'Request Timeout',
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
    ];
    $title = $messages[$code] ?? 'Client Error';
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
@endphp

<x-layouts.error>
    <x-slot:code>{{ $code }}</x-slot:code>

    <x-slot:title>{{ $title }}</x-slot:title>

    <x-slot:description>
        @if($exception->getMessage())
            {{ $exception->getMessage() }}
        @elseif($code === 401)
            You need to be authenticated to access this resource.
        @elseif($code === 403)
            You don't have permission to access this resource.
        @elseif($code === 419)
            Your session has expired. Please refresh the page and try again.
        @elseif($code === 429)
            You've made too many requests. Please wait a moment before trying again.
        @else
            The request could not be processed. Please check the URL and try again.
        @endif
    </x-slot:description>

    <x-slot:icon>
        @if($code === 419)
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        @elseif($code === 403 || $code === 401)
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
            </svg>
        @elseif($code === 429)
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        @else
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        @endif
    </x-slot:icon>

    <x-slot:iconGradient>bg-gradient-to-br from-{{ $primary }}-500 to-{{ $secondary }}-600</x-slot:iconGradient>
    <x-slot:iconShadow>shadow-{{ $primary }}-500/30</x-slot:iconShadow>
    <x-slot:codeGradient>from-{{ $primary }}-400 via-{{ $secondary }}-400 to-{{ $primary }}-400</x-slot:codeGradient>

    @if($code === 419 || $code === 429)
        <x-slot:actions>
            <button
                onclick="location.reload()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-{{ $primary }}-600 to-{{ $secondary }}-600 hover:from-{{ $primary }}-500 hover:to-{{ $secondary }}-500 text-white font-medium rounded-xl shadow-lg shadow-{{ $primary }}-500/25 hover:shadow-{{ $primary }}-500/40 transition-all duration-200"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Page
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
    @endif
</x-layouts.error>

