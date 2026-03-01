<?php

use App\Models\Secret;
use function Laravel\Folio\{name, middleware, render};

name('secret.show');
middleware(['throttle:secrets']);

render(function ($view, Secret $secret) {
    $primary = config('branding.primary_color');
    $secondary = config('branding.secondary_color');
    $accent = config('branding.accent_color');
    $showContainer = config('branding.logo.show_container');

    // Normalize and sanitize logo path (prevent directory traversal)
    $logoPath = config('branding.logo.image');
    if ($logoPath) {
        // Remove any directory traversal attempts
        $logoPath = str_replace(['..', '\\'], '', $logoPath);
        if (!str_starts_with($logoPath, '/') && !str_starts_with($logoPath, 'http')) {
            $logoPath = '/' . $logoPath;
        }
    }

    return response($view->with([
        'secretId' => $secret->id,
        'createdAt' => $secret->created_at->format('M j, Y \a\t g:i A'),
        'primary' => $primary,
        'secondary' => $secondary,
        'accent' => $accent,
        'showContainer' => $showContainer,
        'logoPath' => $logoPath,
    ]))->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
       ->header('Pragma', 'no-cache')
       ->header('Expires', '0');
});
?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }} - View Secret</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|jetbrains-mono:400,500" rel="stylesheet" />

        @vite(['resources/css/app.css'])

        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

        <style>
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }
            .mono, code, pre {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
            }
            .prose {
                color: #e2e8f0;
            }
            .prose h1, .prose h2, .prose h3, .prose h4 {
                color: #f8fafc;
                font-weight: 600;
                margin-top: 1.5em;
                margin-bottom: 0.5em;
            }
            .prose > :first-child {
                margin-top: 0;
            }
            .prose h1 { font-size: 2em; }
            .prose h2 { font-size: 1.5em; }
            .prose h3 { font-size: 1.25em; }
            .prose p { margin-bottom: 1em; line-height: 1.7; }
            .prose ul, .prose ol { margin: 1em 0; padding-left: 2em; }
            .prose li { margin: 0.5em 0; }
            .prose ul { list-style-type: disc; }
            .prose ol { list-style-type: decimal; }
            .prose code {
                background: rgba(139, 92, 246, 0.15);
                color: #c4b5fd;
                padding: 0.2em 0.4em;
                border-radius: 0.375rem;
                font-size: 0.9em;
            }
            .prose pre {
                background: rgba(15, 23, 42, 0.8);
                border: 1px solid rgba(148, 163, 184, 0.1);
                border-radius: 0.75rem;
                padding: 1em 1.25em;
                overflow-x: auto;
                margin: 1.5em 0;
            }
            .prose pre code {
                background: transparent;
                padding: 0;
                color: #e2e8f0;
            }
            .prose a {
                color: #a78bfa;
                text-decoration: underline;
                text-underline-offset: 2px;
            }
            .prose a:hover {
                color: #c4b5fd;
            }
            .prose blockquote {
                border-left: 3px solid #8b5cf6;
                padding-left: 1em;
                margin: 1.5em 0;
                color: #94a3b8;
                font-style: italic;
            }
            .prose hr {
                border-color: rgba(148, 163, 184, 0.2);
                margin: 2em 0;
            }
            .prose strong { color: #f8fafc; }
            .prose em { color: #cbd5e1; }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-900 via-{{ $primary }}-950 to-slate-900 text-white antialiased">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-20 w-96 h-96 bg-{{ $primary }}-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-{{ $secondary }}-600/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-{{ $accent }}-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative min-h-screen flex flex-col items-center justify-center p-6">
            <div class="w-full max-w-3xl">
                <!-- Header with clickable logo -->
                <div class="text-center mb-8">
                    @if($showContainer)
                        <a href="{{ route('home') }}" class="inline-flex items-center justify-center {{ config('branding.logo_size.large') }} mb-5 p-3.5 rounded-2xl bg-gradient-to-br from-{{ $primary }}-500 to-{{ $secondary }}-600 shadow-lg shadow-{{ $primary }}-500/30 hover:scale-105 transition-transform duration-200">
                            @if(config('branding.logo.type') === 'image')
                                <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" class="w-full h-full object-contain">
                            @else
                                {!! config('branding.logo.svg') !!}
                            @endif
                        </a>
                    @else
                        <a href="{{ route('home') }}" class="inline-block mb-5 hover:scale-105 transition-transform duration-200">
                            @if(config('branding.logo.type') === 'image')
                                <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" class="h-14 w-auto">
                            @else
                                <div class="{{ config('branding.logo_size.large') }}">
                                    {!! config('branding.logo.svg') !!}
                                </div>
                            @endif
                        </a>
                    @endif
                </div>

                <!-- Confirmation View (shown when confirmation is required) -->
                <div id="confirmation-view" class="hidden">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/30">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight mb-3">
                            View Secret?
                        </h1>
                        <p class="text-slate-400 max-w-md mx-auto">
                            Someone has shared a secret with you. Once you view it, the secret will be permanently deleted.
                        </p>
                    </div>

                    <div id="confirmation-warning" class="relative group mb-8">
                        <div class="absolute -inset-1 bg-gradient-to-r from-amber-600 via-orange-600 to-amber-600 rounded-2xl opacity-30 blur-sm"></div>
                        <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-slate-200 mb-1">This action cannot be undone</h3>
                                    <p class="text-slate-400 text-sm">
                                        For security, secrets are deleted immediately after being viewed. Make sure you're ready to view and save the content if needed.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="password-section" class="hidden mb-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-{{ $primary }}-600 via-{{ $secondary }}-600 to-{{ $primary }}-600 rounded-2xl opacity-30 blur-sm"></div>
                            <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="shrink-0 w-10 h-10 rounded-lg bg-{{ $primary }}-500/20 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-{{ $primary }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-slate-200">Password Required</h3>
                                        <p class="text-slate-400 text-sm">This secret is password protected</p>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input
                                        type="password"
                                        id="access-password"
                                        placeholder="Enter password"
                                        class="w-full px-4 py-3 bg-slate-800/80 border border-slate-600 rounded-xl text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-{{ $primary }}-500/50 focus:border-{{ $primary }}-500"
                                        onkeypress="if(event.key === 'Enter') confirmAndRetrieve()"
                                    >
                                    <button
                                        type="button"
                                        onclick="toggleAccessPasswordVisibility()"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
                                    >
                                        <svg id="access-eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <svg id="access-eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div id="password-error" class="hidden mt-3 flex items-center gap-2 text-red-400 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span id="password-error-text">Incorrect password</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button
                            id="confirm-btn"
                            onclick="confirmAndRetrieve()"
                            class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span id="confirm-btn-text">View Secret</span>
                        </button>

                        <a
                            href="{{ route('home') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl border border-slate-700 hover:border-slate-600 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancel
                        </a>
                    </div>
                </div>

                <!-- Success View (shown when decryption works) -->
                <div id="success-view" class="hidden">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-semibold tracking-tight mb-2">
                            Secret retrieved successfully
                        </h1>
                        <p class="text-slate-400" id="created-at-text">
                            Shared on {{ $createdAt }}
                        </p>
                    </div>

                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 rounded-2xl opacity-40 blur-sm"></div>
                        <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-8 min-h-[200px]">
                            <div id="content" class="max-w-none">
                                <!-- Decrypted content will appear here -->
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-end gap-4">
                        <div class="flex items-center gap-2">
                            <button
                                id="copy-md-btn"
                                onclick="copyMarkdown()"
                                style="display: none;"
                                class="inline-flex items-center gap-2 px-5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl border border-slate-700 hover:border-slate-600 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span id="copy-md-text">Copy as Markdown</span>
                            </button>
                            <button
                                onclick="copyContent()"
                                class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span id="copy-content-text">Copy</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error View (shown when decryption fails) -->
                <div id="error-view" class="hidden">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-semibold tracking-tight mb-3">
                            Decryption Failed
                        </h1>
                        <p class="text-slate-400 max-w-md mx-auto mb-2">
                            The secret could not be decrypted. This usually means the encryption key in the URL is missing or corrupted.
                        </p>
                        <p class="text-slate-500 text-sm">
                            Make sure you copied the complete URL including the <code class="text-{{ $primary }}-400 bg-slate-800 px-1.5 py-0.5 rounded">#key</code> at the end.
                        </p>
                    </div>

                    <div class="relative group mb-6">
                        <div class="absolute -inset-1 bg-gradient-to-r from-red-600 via-rose-600 to-red-600 rounded-2xl opacity-30 blur-sm"></div>
                        <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-slate-200 mb-1">Missing Encryption Key</h3>
                                    <p class="text-slate-400 text-sm" id="error-detail">
                                        No encryption key found in the URL. The link should end with <code class="text-{{ $primary }}-400 bg-slate-800 px-1 py-0.5 rounded text-xs">#xxxxxxxx</code>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Input Section (shown when we have cached encrypted content) -->
                    <div id="retry-key-section" class="hidden mb-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-{{ $primary }}-600 via-{{ $secondary }}-600 to-{{ $primary }}-600 rounded-2xl opacity-30 blur-sm"></div>
                            <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="shrink-0 w-10 h-10 rounded-lg bg-{{ $primary }}-500/20 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-{{ $primary }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-slate-200">Enter Encryption Key</h3>
                                        <p class="text-slate-400 text-sm">Paste the correct key or full link to try again</p>
                                    </div>
                                </div>

                                <input
                                    type="text"
                                    id="retry-key-input"
                                    placeholder="Enter key (e.g., Ab3xK9mZ) or paste full link"
                                    class="w-full px-4 py-3 bg-slate-800/80 border border-slate-600 rounded-xl text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-{{ $primary }}-500/50 focus:border-{{ $primary }}-500 mono"
                                    onkeypress="if(event.key === 'Enter') retryDecryption()"
                                >

                                <div id="retry-key-error" class="hidden mt-3 flex items-center gap-2 text-red-400 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span id="retry-key-error-text">Invalid key</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button
                            onclick="retryDecryption()"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-{{ $primary }}-600 to-{{ $secondary }}-600 hover:from-{{ $primary }}-500 hover:to-{{ $secondary }}-500 text-white font-medium rounded-xl shadow-lg shadow-{{ $primary }}-500/25 hover:shadow-{{ $primary }}-500/40 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Retry Decryption
                        </button>
                    </div>
                </div>

                <!-- Loading View -->
                <div id="loading-view">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 mb-5 rounded-2xl bg-gradient-to-br from-{{ $primary }}-500 to-{{ $secondary }}-600 shadow-lg shadow-{{ $primary }}-500/30 animate-pulse">
                            <svg class="w-7 h-7 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight mb-2" id="loading-title">
                            Loading...
                        </h1>
                        <p class="text-slate-400" id="loading-text">
                            Please wait
                        </p>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-800 text-center">
                    <p class="text-sm text-slate-500 mono">
                        ID: {{ $secretId }}
                    </p>
                </div>
            </div>
        </div>

        <script>
            // Secret metadata
            const secretId = @json($secretId);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Store secret data
            let encryptedContent = null;
            let secretCreatedAt = null;
            let requiresConfirmation = false;
            let requiresPassword = false;
            let markdownEnabled = false;

            // Store decrypted content for copy functionality
            let decryptedRawContent = null;

            // Toggle password visibility
            function toggleAccessPasswordVisibility() {
                const input = document.getElementById('access-password');
                const eyeIcon = document.getElementById('access-eye-icon');
                const eyeOffIcon = document.getElementById('access-eye-off-icon');

                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.classList.add('hidden');
                    eyeOffIcon.classList.remove('hidden');
                } else {
                    input.type = 'password';
                    eyeIcon.classList.remove('hidden');
                    eyeOffIcon.classList.add('hidden');
                }
            }

            // Hide all views
            function hideAllViews() {
                document.getElementById('loading-view').classList.add('hidden');
                document.getElementById('confirmation-view').classList.add('hidden');
                document.getElementById('success-view').classList.add('hidden');
                document.getElementById('error-view').classList.add('hidden');
            }

            // Show loading view
            function showLoading(title = 'Loading...', text = 'Please wait') {
                hideAllViews();
                document.getElementById('loading-title').textContent = title;
                document.getElementById('loading-text').textContent = text;
                document.getElementById('loading-view').classList.remove('hidden');
            }

            // Show confirmation view
            function showConfirmation() {
                hideAllViews();

                // Show password section if required
                if (requiresPassword) {
                    document.getElementById('password-section').classList.remove('hidden');
                    document.getElementById('access-password').value = '';
                    document.getElementById('password-error').classList.add('hidden');
                } else {
                    document.getElementById('password-section').classList.add('hidden');
                }

                document.getElementById('confirmation-view').classList.remove('hidden');
            }

            // Show error view
            function showError(detail) {
                decryptedRawContent = null;
                hideAllViews();
                document.getElementById('error-view').classList.remove('hidden');
                if (detail) {
                    document.getElementById('error-detail').textContent = detail;
                }

                // Show the key input section if we have cached encrypted content
                const retryKeySection = document.getElementById('retry-key-section');
                const retryKeyError = document.getElementById('retry-key-error');
                if (encryptedContent) {
                    retryKeySection.classList.remove('hidden');
                    retryKeyError.classList.add('hidden');
                    document.getElementById('retry-key-input').value = '';
                    document.getElementById('retry-key-input').focus();
                } else {
                    retryKeySection.classList.add('hidden');
                }
            }

            // Show success view with decrypted content
            function showSuccess(content, createdAt) {
                decryptedRawContent = content;
                hideAllViews();
                document.getElementById('success-view').classList.remove('hidden');

                const contentEl = document.getElementById('content');
                const copyMdBtn = document.getElementById('copy-md-btn');

                if (markdownEnabled) {
                    contentEl.classList.add('prose');
                    contentEl.innerHTML = marked.parse(content);
                    copyMdBtn.style.display = '';
                } else {
                    contentEl.classList.remove('prose');
                    contentEl.innerHTML = '';
                    const pre = document.createElement('pre');
                    pre.className = 'whitespace-pre-wrap break-words text-slate-200 text-sm leading-relaxed';
                    pre.textContent = content;
                    contentEl.appendChild(pre);
                    copyMdBtn.style.display = 'none';
                }

                if (createdAt) {
                    document.getElementById('created-at-text').textContent = 'Shared on ' + createdAt;
                }

                history.replaceState({ secretViewed: true }, '', window.location.pathname);
            }

            // Derive encryption key from password using PBKDF2
            async function deriveKey(password) {
                const encoder = new TextEncoder();
                const keyMaterial = await crypto.subtle.importKey(
                    'raw',
                    encoder.encode(password),
                    'PBKDF2',
                    false,
                    ['deriveKey']
                );

                return crypto.subtle.deriveKey(
                    {
                        name: 'PBKDF2',
                        salt: encoder.encode('private-share-salt'),
                        iterations: 100000,
                        hash: 'SHA-256'
                    },
                    keyMaterial,
                    { name: 'AES-GCM', length: 256 },
                    false,
                    ['encrypt', 'decrypt']
                );
            }

            // Decrypt content
            async function decryptContent(encryptedBase64, password) {
                try {
                    const decoder = new TextDecoder();
                    const key = await deriveKey(password);

                    // Decode base64 to bytes
                    const combined = Uint8Array.from(atob(encryptedBase64), c => c.charCodeAt(0));

                    // Extract IV (first 12 bytes) and encrypted data
                    const iv = combined.slice(0, 12);
                    const encrypted = combined.slice(12);

                    const decrypted = await crypto.subtle.decrypt(
                        { name: 'AES-GCM', iv: iv },
                        key,
                        encrypted
                    );

                    return decoder.decode(decrypted);
                } catch (error) {
                    console.error('Decryption failed:', error);
                    throw error;
                }
            }

            // Validate encryption key from URL
            function getEncryptionKey() {
                const hash = window.location.hash;

                if (!hash || hash.length < 2) {
                    return { valid: false, error: 'No encryption key found in the URL. The link should end with #xxxxxxxx where xxxxxxxx is the 8-character key.' };
                }

                const encryptionKey = hash.substring(1);

                if (encryptionKey.length !== 8) {
                    return { valid: false, error: `Invalid encryption key length. Expected 8 characters, got ${encryptionKey.length}. Make sure you copied the complete URL.` };
                }

                return { valid: true, key: encryptionKey };
            }

            // Check secret requirements
            async function checkSecretRequirements() {
                showLoading('Checking...', 'Verifying secret requirements');

                // First validate the encryption key
                const keyResult = getEncryptionKey();
                if (!keyResult.valid) {
                    showError(keyResult.error);
                    return;
                }

                try {
                    const response = await fetch(`/api/secrets/${secretId}/check`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    if (!response.ok) {
                        if (response.status === 404) {
                            showError('This secret has already been viewed or does not exist.');
                            return;
                        }
                        throw new Error('Failed to check secret');
                    }

                    const data = await response.json();
                    requiresConfirmation = data.requires_confirmation;
                    requiresPassword = data.requires_password;
                    markdownEnabled = data.markdown_enabled;

                    if (requiresConfirmation || requiresPassword) {
                        showConfirmation();
                    } else {
                        // Otherwise, retrieve directly
                        await retrieveAndDecrypt();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Failed to load secret. Please try again.');
                }
            }

            // Confirm and retrieve the secret
            async function confirmAndRetrieve() {
                const btn = document.getElementById('confirm-btn');
                const btnText = document.getElementById('confirm-btn-text');
                const passwordError = document.getElementById('password-error');
                const passwordErrorText = document.getElementById('password-error-text');

                // Hide any previous password errors
                passwordError.classList.add('hidden');

                // Validate password if required
                const password = requiresPassword ? document.getElementById('access-password').value : null;
                if (requiresPassword && !password) {
                    passwordError.classList.remove('hidden');
                    passwordErrorText.textContent = 'Please enter the password';
                    document.getElementById('access-password').focus();
                    return;
                }

                btn.disabled = true;
                btnText.textContent = 'Retrieving...';

                await retrieveAndDecrypt(password);

                btn.disabled = false;
                btnText.textContent = 'View Secret';
            }

            // Retrieve the secret from server and decrypt
            async function retrieveAndDecrypt(password = null) {
                showLoading('Retrieving...', 'Fetching your secret');

                const keyResult = getEncryptionKey();
                if (!keyResult.valid) {
                    showError(keyResult.error);
                    return;
                }

                try {
                    const payload = {};
                    if (password) {
                        payload.password = password;
                    }

                    const response = await fetch(`/api/secrets/${secretId}/retrieve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        if (response.status === 404) {
                            showError('This secret has already been viewed or does not exist.');
                            return;
                        }

                        const errorData = await response.json();

                        if (response.status === 403 && errorData.error === 'invalid_password') {
                            // Wrong password - go back to confirmation view
                            showConfirmation();
                            const passwordError = document.getElementById('password-error');
                            const passwordErrorText = document.getElementById('password-error-text');
                            passwordError.classList.remove('hidden');
                            passwordErrorText.textContent = errorData.message || 'Incorrect password';
                            document.getElementById('access-password').focus();
                            return;
                        }

                        throw new Error(errorData.message || 'Failed to retrieve secret');
                    }

                    const data = await response.json();
                    encryptedContent = data.content;
                    secretCreatedAt = data.created_at;
                    markdownEnabled = data.markdown_enabled;

                    // Now decrypt
                    showLoading('Decrypting...', 'Please wait while we decrypt your secret');

                    try {
                        const content = await decryptContent(encryptedContent, keyResult.key);
                        showSuccess(content, secretCreatedAt);
                    } catch (error) {
                        showError('Decryption failed. The encryption key is incorrect. Enter the correct key below and try again.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError(error.message || 'Failed to retrieve secret. Please try again.');
                }
            }

            // Extract encryption key from input (handles both raw key and full URL)
            function extractKeyFromInput(input) {
                input = input.trim();

                // If it contains a hash, extract the key from it (full URL case)
                if (input.includes('#')) {
                    const hashIndex = input.indexOf('#');
                    input = input.substring(hashIndex + 1);
                }

                // Validate key length
                if (input.length !== 8) {
                    return { valid: false, error: `Invalid key length. Expected 8 characters, got ${input.length}.` };
                }

                return { valid: true, key: input };
            }

            // Retry decryption with key from input or URL
            async function retryDecryption() {
                const retryKeyError = document.getElementById('retry-key-error');
                const retryKeyErrorText = document.getElementById('retry-key-error-text');

                // If we have cached encrypted content, use the key from input
                if (encryptedContent) {
                    const inputValue = document.getElementById('retry-key-input').value;

                    if (!inputValue.trim()) {
                        retryKeyError.classList.remove('hidden');
                        retryKeyErrorText.textContent = 'Please enter an encryption key or paste the full link';
                        document.getElementById('retry-key-input').focus();
                        return;
                    }

                    const keyResult = extractKeyFromInput(inputValue);
                    if (!keyResult.valid) {
                        retryKeyError.classList.remove('hidden');
                        retryKeyErrorText.textContent = keyResult.error;
                        return;
                    }

                    retryKeyError.classList.add('hidden');
                    showLoading('Decrypting...', 'Please wait while we decrypt your secret');

                    try {
                        const content = await decryptContent(encryptedContent, keyResult.key);
                        showSuccess(content, secretCreatedAt);
                    } catch (error) {
                        showError('Decryption failed. The encryption key is incorrect. Please check and try again.');
                        document.getElementById('retry-key-input').focus();
                    }
                } else {
                    // No cached content, start from the beginning
                    checkSecretRequirements();
                }
            }

            // Copy content to clipboard (plain text from rendered output, or raw if no markdown)
            function copyContent() {
                if (!decryptedRawContent) return;

                let textToCopy;
                if (markdownEnabled) {
                    const contentEl = document.getElementById('content');
                    textToCopy = contentEl.innerText || contentEl.textContent;
                } else {
                    textToCopy = decryptedRawContent;
                }

                navigator.clipboard.writeText(textToCopy).then(function() {
                    const btn = document.getElementById('copy-content-text');
                    btn.textContent = 'Copied!';
                    setTimeout(() => { btn.textContent = 'Copy'; }, 2000);
                });
            }

            // Copy original markdown source to clipboard
            function copyMarkdown() {
                if (!decryptedRawContent) return;

                navigator.clipboard.writeText(decryptedRawContent).then(function() {
                    const btn = document.getElementById('copy-md-text');
                    btn.textContent = 'Copied!';
                    setTimeout(() => { btn.textContent = 'Copy as Markdown'; }, 2000);
                });
            }

            // Handle page restoration from bfcache (back/forward navigation)
            // This prevents showing the secret again when user presses back button
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    decryptedRawContent = null;
                    encryptedContent = null;
                    document.getElementById('content').innerHTML = '';

                    // Show error that secret has been viewed
                    showError('This secret has already been viewed and cannot be displayed again.');
                }
            });

            window.addEventListener('pagehide', function() {
                decryptedRawContent = null;
                document.getElementById('content').innerHTML = '';
            });

            // Start checking requirements on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Check if this is a restored page state where secret was already viewed
                if (history.state && history.state.secretViewed) {
                    showError('This secret has already been viewed and cannot be displayed again.');
                    return;
                }
                checkSecretRequirements();
            });

            // Listen for hash changes
            window.addEventListener('hashchange', function() {
                // Don't process if secret was already viewed
                if (history.state && history.state.secretViewed) {
                    showError('This secret has already been viewed and cannot be displayed again.');
                    return;
                }
                checkSecretRequirements();
            });
        </script>
    </body>
</html>
