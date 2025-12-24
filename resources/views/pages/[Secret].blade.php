<?php

use App\Models\Secret;
use function Laravel\Folio\{name, middleware};

name('secret.show');
middleware(['throttle:secrets']);
?>

@php
/** @var Secret $secret */
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Private Share') }} - View Secret</title>

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
    <body class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 text-white antialiased">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-20 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-indigo-600/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-fuchsia-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative min-h-screen flex flex-col items-center justify-center p-6">
            <div class="w-full max-w-3xl">
                <!-- Success View (shown when decryption works) -->
                <div id="success-view" class="hidden">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 mb-5 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/30">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight mb-2">
                            Secret Content
                        </h1>
                        <p class="text-slate-400">
                            Shared on {{ $secret->created_at->format('M j, Y \a\t g:i A') }}
                        </p>
                    </div>

                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 rounded-2xl opacity-40 blur-sm"></div>
                        <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-8 min-h-[200px]">
                            <div id="content" class="prose max-w-none">
                                <!-- Decrypted content will appear here -->
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <a
                            href="{{ route('home') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl border border-slate-700 hover:border-slate-600 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create New Secret
                        </a>

                        <button
                            onclick="copyLink()"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-200"
                        >
                            <svg id="copy-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                            <svg id="check-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span id="copy-text">Copy Link</span>
                        </button>
                    </div>
                </div>

                <!-- Error View (shown when decryption fails) -->
                <div id="error-view" class="hidden">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-full bg-gradient-to-br from-red-500 to-rose-600 shadow-lg shadow-red-500/30">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight mb-3">
                            Decryption Failed
                        </h1>
                        <p class="text-slate-400 max-w-md mx-auto mb-2">
                            The secret could not be decrypted. This usually means the encryption key in the URL is missing or corrupted.
                        </p>
                        <p class="text-slate-500 text-sm">
                            Make sure you copied the complete URL including the <code class="text-purple-400 bg-slate-800 px-1.5 py-0.5 rounded">#key</code> at the end.
                        </p>
                    </div>

                    <div class="relative group mb-8">
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
                                        No encryption key found in the URL. The link should end with <code class="text-purple-400 bg-slate-800 px-1 py-0.5 rounded text-xs">#xxxxxxxx</code>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button
                            onclick="retryDecryption()"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Retry Decryption
                        </button>

                        <a
                            href="{{ route('home') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl border border-slate-700 hover:border-slate-600 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create New Secret
                        </a>
                    </div>
                </div>

                <!-- Loading View -->
                <div id="loading-view">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 mb-5 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 shadow-lg shadow-purple-500/30 animate-pulse">
                            <svg class="w-7 h-7 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight mb-2">
                            Decrypting...
                        </h1>
                        <p class="text-slate-400">
                            Please wait while we decrypt your secret
                        </p>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-800 text-center">
                    <p class="text-sm text-slate-500 mono">
                        ID: {{ $secret->id }}
                    </p>
                </div>
            </div>
        </div>

        <script>
            // Store encrypted content from server
            const encryptedContent = @json($secret->content);

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

            // Show error view
            function showError(detail) {
                document.getElementById('loading-view').classList.add('hidden');
                document.getElementById('success-view').classList.add('hidden');
                document.getElementById('error-view').classList.remove('hidden');
                if (detail) {
                    document.getElementById('error-detail').textContent = detail;
                }
            }

            // Show success view with decrypted content
            function showSuccess(content) {
                document.getElementById('loading-view').classList.add('hidden');
                document.getElementById('error-view').classList.add('hidden');
                document.getElementById('success-view').classList.remove('hidden');
                document.getElementById('content').innerHTML = marked.parse(content);
            }

            // Main decryption function
            async function tryDecrypt() {
                // Get encryption key from URL hash
                const hash = window.location.hash;

                if (!hash || hash.length < 2) {
                    showError('No encryption key found in the URL. The link should end with #xxxxxxxx where xxxxxxxx is the 8-character key.');
                    return;
                }

                const encryptionKey = hash.substring(1); // Remove the # character

                if (encryptionKey.length !== 8) {
                    showError(`Invalid encryption key length. Expected 8 characters, got ${encryptionKey.length}. Make sure you copied the complete URL.`);
                    return;
                }

                try {
                    const decryptedContent = await decryptContent(encryptedContent, encryptionKey);
                    showSuccess(decryptedContent);
                } catch (error) {
                    showError('Decryption failed. The encryption key may be incorrect or the data may be corrupted. Try copying the link again.');
                }
            }

            // Retry decryption (re-reads hash from URL)
            function retryDecryption() {
                document.getElementById('error-view').classList.add('hidden');
                document.getElementById('loading-view').classList.remove('hidden');

                // Small delay to show loading state
                setTimeout(tryDecrypt, 300);
            }

            // Copy link to clipboard
            function copyLink() {
                navigator.clipboard.writeText(window.location.href).then(function() {
                    document.getElementById('copy-icon').classList.add('hidden');
                    document.getElementById('check-icon').classList.remove('hidden');
                    document.getElementById('copy-text').textContent = 'Copied!';

                    setTimeout(function() {
                        document.getElementById('copy-icon').classList.remove('hidden');
                        document.getElementById('check-icon').classList.add('hidden');
                        document.getElementById('copy-text').textContent = 'Copy Link';
                    }, 2000);
                });
            }

            // Start decryption on page load
            document.addEventListener('DOMContentLoaded', tryDecrypt);
        </script>
    </body>
</html>

