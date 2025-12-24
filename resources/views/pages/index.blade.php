<?php

use function Laravel\Folio\name;

name('home');

$primary = config('branding.primary_color');
$secondary = config('branding.secondary_color');
$accent = config('branding.accent_color');
$showContainer = config('branding.logo.show_container');

// Normalize logo path
$logoPath = config('branding.logo.image');
if ($logoPath && !str_starts_with($logoPath, '/') && !str_starts_with($logoPath, 'http')) {
    $logoPath = '/' . $logoPath;
}
?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }} - Share Secrets</title>

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
            textarea, .mono {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
            }
            .prose {
                color: #e2e8f0;
            }
            .prose h1, .prose h2, .prose h3, .prose h4 {
                color: #f8fafc;
                font-weight: 600;
                margin-top: 1em;
                margin-bottom: 0.5em;
            }
            .prose h1 { font-size: 1.75em; }
            .prose h2 { font-size: 1.4em; }
            .prose h3 { font-size: 1.15em; }
            .prose p { margin-bottom: 0.75em; line-height: 1.6; }
            .prose ul, .prose ol { margin: 0.75em 0; padding-left: 1.5em; }
            .prose li { margin: 0.35em 0; }
            .prose ul { list-style-type: disc; }
            .prose ol { list-style-type: decimal; }
            .prose code {
                background: rgba(139, 92, 246, 0.15);
                color: #c4b5fd;
                padding: 0.15em 0.35em;
                border-radius: 0.3rem;
                font-size: 0.85em;
                font-family: 'JetBrains Mono', ui-monospace, monospace;
            }
            .prose pre {
                background: rgba(15, 23, 42, 0.8);
                border: 1px solid rgba(148, 163, 184, 0.1);
                border-radius: 0.5rem;
                padding: 0.75em 1em;
                overflow-x: auto;
                margin: 1em 0;
            }
            .prose pre code {
                background: transparent;
                padding: 0;
                color: #e2e8f0;
            }
            .prose a {
                color: #a78bfa;
                text-decoration: underline;
            }
            .prose blockquote {
                border-left: 3px solid #8b5cf6;
                padding-left: 1em;
                margin: 1em 0;
                color: #94a3b8;
                font-style: italic;
            }
            .prose strong { color: #f8fafc; }
            .prose em { color: #cbd5e1; }

            /* Toolbar buttons */
            .toolbar-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.25rem;
                padding: 0.375rem 0.5rem;
                border-radius: 0.5rem;
                color: #94a3b8;
                transition: all 0.15s ease;
                font-size: 0.75rem;
            }
            .toolbar-btn:hover {
                background: rgba(139, 92, 246, 0.2);
                color: #e2e8f0;
            }
            .toolbar-btn:active {
                background: rgba(139, 92, 246, 0.3);
                transform: scale(0.95);
            }

            /* Fade in animation */
            .animate-fadeIn {
                animation: fadeIn 0.2s ease-out;
            }
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-8px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Toggle switch (compact) */
            .toggle-switch-sm {
                position: relative;
                width: 36px;
                height: 20px;
                background: #334155;
                border-radius: 9999px;
                cursor: pointer;
                transition: background-color 0.2s ease;
                flex-shrink: 0;
            }
            .toggle-switch-sm::after {
                content: '';
                position: absolute;
                top: 2px;
                left: 2px;
                width: 16px;
                height: 16px;
                background: #94a3b8;
                border-radius: 9999px;
                transition: all 0.2s ease;
            }
            .toggle-input:checked + .toggle-switch-sm {
                background: #9333ea;
            }
            .toggle-input:checked + .toggle-switch-sm::after {
                transform: translateX(16px);
                background: white;
            }
            .toggle-input:focus + .toggle-switch-sm {
                box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.3);
            }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-900 via-{{ $primary }}-950 to-slate-900 text-white antialiased">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -left-20 w-96 h-96 bg-{{ $primary }}-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-{{ $secondary }}-600/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-{{ $accent }}-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative min-h-screen flex flex-col items-center justify-center p-6">
            <div class="w-full max-w-5xl">
                <div class="text-center mb-10">
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
                    <h1 class="text-3xl font-semibold tracking-tight mb-2">
                        {{ config('app.name') }}
                    </h1>
                    <p class="text-slate-400">
                        {{ config('branding.tagline') }}
                    </p>
                    <p class="text-slate-500 text-sm mt-2">
                        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Secrets auto-expire after 30 days
                    </p>
                </div>

                <!-- Editor View -->
                <div id="editor-view">
                    <!-- Markdown Toolbar -->
                    <div class="flex flex-wrap items-center gap-1 mb-2 p-2 bg-slate-800/60 backdrop-blur-sm border border-slate-700/50 rounded-xl">
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="insertMarkdown('heading')" class="toolbar-btn" title="Heading (Ctrl+H)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h10m-7 4h4"/></svg>
                                <span class="text-xs">H</span>
                            </button>
                            <button type="button" onclick="insertMarkdown('bold')" class="toolbar-btn" title="Bold (Ctrl+B)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('italic')" class="toolbar-btn" title="Italic (Ctrl+I)">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="14" y1="4" x2="10" y2="20"/><line x1="10" y1="4" x2="16" y2="4"/><line x1="8" y1="20" x2="14" y2="20"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('strikethrough')" class="toolbar-btn" title="Strikethrough">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="12" x2="20" y2="12"/><path d="M16 4H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H7"/></svg>
                            </button>
                        </div>

                        <div class="w-px h-5 bg-slate-700 mx-1"></div>

                        <div class="flex items-center gap-1">
                            <button type="button" onclick="insertMarkdown('link')" class="toolbar-btn" title="Link (Ctrl+K)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('code')" class="toolbar-btn" title="Inline Code">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('codeblock')" class="toolbar-btn" title="Code Block">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>
                        </div>

                        <div class="w-px h-5 bg-slate-700 mx-1"></div>

                        <div class="flex items-center gap-1">
                            <button type="button" onclick="insertMarkdown('quote')" class="toolbar-btn" title="Quote">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('bullet')" class="toolbar-btn" title="Bullet List">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="5" cy="6" r="1.5" fill="currentColor"/><circle cx="5" cy="12" r="1.5" fill="currentColor"/><circle cx="5" cy="18" r="1.5" fill="currentColor"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('numbered')" class="toolbar-btn" title="Numbered List">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="20" y2="6"/><line x1="10" y1="12" x2="20" y2="12"/><line x1="10" y1="18" x2="20" y2="18"/><text x="4" y="8" font-size="7" font-weight="bold" fill="currentColor" stroke="none">1</text><text x="4" y="14" font-size="7" font-weight="bold" fill="currentColor" stroke="none">2</text><text x="4" y="20" font-size="7" font-weight="bold" fill="currentColor" stroke="none">3</text></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('checkbox')" class="toolbar-btn" title="Checkbox List">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                        </div>

                        <div class="w-px h-5 bg-slate-700 mx-1"></div>

                        <div class="flex items-center gap-1">
                            <button type="button" onclick="insertMarkdown('table')" class="toolbar-btn group relative" title="Insert Table">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('hr')" class="toolbar-btn" title="Horizontal Rule">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <button type="button" onclick="insertMarkdown('image')" class="toolbar-btn" title="Image">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>
                        </div>

                        <div class="hidden sm:block w-px h-5 bg-slate-700 mx-1"></div>

                        <div class="hidden sm:flex items-center gap-1 ml-auto">
                            <span class="text-xs text-slate-500">Markdown supported</span>
                        </div>
                    </div>

                    <div class="relative group mb-4">
                        <div class="absolute -inset-1 bg-gradient-to-r from-{{ $primary }}-600 via-{{ $secondary }}-600 to-{{ $primary }}-600 rounded-2xl opacity-40 blur-sm group-focus-within:opacity-60 transition-opacity"></div>
                        <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-0 bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
                            <!-- Textarea -->
                            <div class="relative">
                                <div class="absolute top-3 left-4 text-xs font-medium text-slate-500 uppercase tracking-wider">Editor</div>
                                <textarea
                                    id="content"
                                    rows="10"
                                    placeholder="Write your secret here...&#10;&#10;**Bold**, *italic*, `code`&#10;- Lists&#10;> Quotes"
                                    class="w-full h-full min-h-[250px] px-4 pt-10 pb-4 bg-transparent text-slate-100 placeholder-slate-600 text-sm leading-relaxed resize-none focus:outline-none border-b lg:border-b-0 lg:border-r border-slate-700/50"
                                    oninput="updatePreview()"
                                ></textarea>
                            </div>
                            <!-- Preview -->
                            <div class="relative">
                                <div class="absolute top-3 left-4 text-xs font-medium text-slate-500 uppercase tracking-wider">Preview</div>
                                <div id="preview" class="prose w-full h-full min-h-[250px] px-4 pt-10 pb-4 text-sm overflow-y-auto">
                                    <p class="text-slate-600 italic">Preview will appear here...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Bar: Security Options + Share Button -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-3 bg-slate-800/40 backdrop-blur-sm border border-slate-700/50 rounded-xl">
                        <!-- Security Options (compact) -->
                        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                            <!-- Confirmation Toggle -->
                            <label class="flex items-center gap-2 cursor-pointer group" title="Recipient must confirm before viewing">
                                <input type="checkbox" id="requires-confirmation" class="toggle-input sr-only">
                                <div class="toggle-switch-sm"></div>
                                <span class="text-sm text-slate-400 group-hover:text-slate-200 transition-colors">Require confirmation</span>
                            </label>

                            <!-- Password Toggle -->
                            <label class="flex items-center gap-2 cursor-pointer group" title="Require a password to view">
                                <input type="checkbox" id="enable-password" class="toggle-input sr-only" onchange="togglePasswordField()">
                                <div class="toggle-switch-sm"></div>
                                <span class="text-sm text-slate-400 group-hover:text-slate-200 transition-colors">Require password</span>
                            </label>

                            <!-- Password Input (inline, hidden by default) -->
                            <div id="password-field" class="hidden animate-fadeIn">
                                <div class="relative">
                                    <input
                                        type="password"
                                        id="secret-password"
                                        placeholder="Min 4 chars"
                                        class="w-40 sm:w-52 px-3 py-1.5 pr-8 bg-slate-900/80 border border-slate-600 rounded-lg text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-{{ $primary }}-500/50 focus:border-{{ $primary }}-500"
                                    >
                                    <button
                                        type="button"
                                        onclick="togglePasswordVisibility()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
                                    >
                                        <svg id="password-eye-open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <svg id="password-eye-closed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Share Button -->
                        <button
                            type="button"
                            id="share-btn"
                            onclick="shareSecret()"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-{{ $primary }}-600 to-{{ $secondary }}-600 hover:from-{{ $primary }}-500 hover:to-{{ $secondary }}-500 text-white font-medium rounded-xl shadow-lg shadow-{{ $primary }}-500/25 hover:shadow-{{ $primary }}-500/40 transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none whitespace-nowrap"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                            </svg>
                            <span id="share-btn-text">Share Secret</span>
                        </button>
                    </div>

                    <div id="error-message" class="hidden mt-3 flex items-center gap-2 text-red-400 text-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span id="error-text"></span>
                    </div>
                </div>

                <!-- Success View (hidden by default) -->
                <div id="success-view" class="hidden">
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 rounded-2xl opacity-40 blur-sm"></div>
                        <div class="relative bg-slate-900/90 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-8 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/30">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-semibold mb-2">Secret Created!</h2>
                            <p class="text-slate-400 mb-2">Share this link. The encryption key is in the URL—don't lose it!</p>
                            <p class="text-slate-500 text-sm mb-4 flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                This secret will auto-expire in 30 days if not viewed
                            </p>

                            <div id="enabled-features" class="hidden flex flex-wrap items-center justify-center gap-4 mb-4 text-sm"></div>

                            <div class="relative mb-6">
                                <input
                                    type="text"
                                    id="secret-link"
                                    readonly
                                    class="w-full px-4 py-3 pr-24 bg-slate-800/80 border border-slate-700 rounded-xl text-slate-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                                />
                                <button
                                    onclick="copyLink()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <span id="copy-btn-text">Copy</span>
                                </button>
                            </div>

                            <div class="flex items-center justify-center gap-2 text-amber-400 text-sm mb-6">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span>The encryption key after # is required to decrypt. Save this full URL!</span>
                            </div>

                            <button
                                onclick="resetForm()"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium rounded-xl border border-slate-700 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Create Another Secret
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-800">
                    <div class="flex items-center justify-center gap-6 text-sm text-slate-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>End-to-End Encrypted</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            <span>Key Never Leaves Browser</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Markdown Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Update preview as user types
            function updatePreview() {
                const content = document.getElementById('content').value;
                const preview = document.getElementById('preview');

                if (content.trim() === '') {
                    preview.innerHTML = '<p class="text-slate-600 italic">Preview will appear here...</p>';
                } else {
                    preview.innerHTML = marked.parse(content);
                }
            }

            // Insert markdown at cursor position
            function insertMarkdown(type) {
                const textarea = document.getElementById('content');
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const selectedText = text.substring(start, end);

                let insertion = '';
                let cursorOffset = 0;
                let selectRange = null;

                switch (type) {
                    case 'bold':
                        insertion = `**${selectedText || 'bold text'}**`;
                        if (!selectedText) selectRange = [start + 2, start + 11];
                        break;

                    case 'italic':
                        insertion = `*${selectedText || 'italic text'}*`;
                        if (!selectedText) selectRange = [start + 1, start + 12];
                        break;

                    case 'strikethrough':
                        insertion = `~~${selectedText || 'strikethrough'}~~`;
                        if (!selectedText) selectRange = [start + 2, start + 15];
                        break;

                    case 'heading':
                        const lineStart = text.lastIndexOf('\n', start - 1) + 1;
                        const prefix = start === lineStart ? '' : '\n';
                        insertion = `${prefix}## ${selectedText || 'Heading'}`;
                        if (!selectedText) selectRange = [start + prefix.length + 3, start + prefix.length + 10];
                        break;

                    case 'link':
                        if (selectedText) {
                            insertion = `[${selectedText}](url)`;
                            selectRange = [start + selectedText.length + 3, start + selectedText.length + 6];
                        } else {
                            insertion = '[link text](url)';
                            selectRange = [start + 1, start + 10];
                        }
                        break;

                    case 'image':
                        insertion = `![${selectedText || 'alt text'}](image-url)`;
                        if (!selectedText) selectRange = [start + 2, start + 10];
                        break;

                    case 'code':
                        insertion = `\`${selectedText || 'code'}\``;
                        if (!selectedText) selectRange = [start + 1, start + 5];
                        break;

                    case 'codeblock':
                        const lang = 'language';
                        insertion = `\n\`\`\`${lang}\n${selectedText || 'code here'}\n\`\`\`\n`;
                        if (!selectedText) selectRange = [start + 5 + lang.length, start + 5 + lang.length + 9];
                        break;

                    case 'quote':
                        insertion = `\n> ${selectedText || 'Quote text here'}\n`;
                        if (!selectedText) selectRange = [start + 3, start + 18];
                        break;

                    case 'bullet':
                        insertion = `\n- ${selectedText || 'List item'}\n- \n- \n`;
                        if (!selectedText) selectRange = [start + 3, start + 12];
                        break;

                    case 'numbered':
                        insertion = `\n1. ${selectedText || 'First item'}\n2. \n3. \n`;
                        if (!selectedText) selectRange = [start + 4, start + 14];
                        break;

                    case 'checkbox':
                        insertion = `\n- [ ] ${selectedText || 'Task item'}\n- [ ] \n- [ ] \n`;
                        if (!selectedText) selectRange = [start + 7, start + 16];
                        break;

                    case 'table':
                        insertion = `
| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
| Cell 1   | Cell 2   | Cell 3   |
| Cell 4   | Cell 5   | Cell 6   |
`;
                        cursorOffset = 3;
                        break;

                    case 'hr':
                        insertion = '\n\n---\n\n';
                        cursorOffset = insertion.length;
                        break;

                    default:
                        return;
                }

                // Insert the markdown
                textarea.value = text.substring(0, start) + insertion + text.substring(end);

                // Update preview
                updatePreview();

                // Set cursor position or selection
                textarea.focus();
                if (selectRange) {
                    textarea.setSelectionRange(selectRange[0], selectRange[1]);
                } else {
                    const newPos = start + insertion.length - cursorOffset;
                    textarea.setSelectionRange(newPos, newPos);
                }
            }

            // Toggle password field visibility
            function togglePasswordField() {
                const checkbox = document.getElementById('enable-password');
                const passwordField = document.getElementById('password-field');
                const passwordInput = document.getElementById('secret-password');

                if (checkbox.checked) {
                    passwordField.classList.remove('hidden');
                    passwordInput.focus();
                } else {
                    passwordField.classList.add('hidden');
                    passwordInput.value = '';
                }
            }

            // Toggle password input visibility (show/hide)
            function togglePasswordVisibility() {
                const passwordInput = document.getElementById('secret-password');
                const eyeOpen = document.getElementById('password-eye-open');
                const eyeClosed = document.getElementById('password-eye-closed');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeOpen.classList.add('hidden');
                    eyeClosed.classList.remove('hidden');
                } else {
                    passwordInput.type = 'password';
                    eyeOpen.classList.remove('hidden');
                    eyeClosed.classList.add('hidden');
                }
            }

            // Keyboard shortcuts
            document.addEventListener('DOMContentLoaded', function() {
                const textarea = document.getElementById('content');

                textarea.addEventListener('keydown', function(e) {
                    if (e.ctrlKey || e.metaKey) {
                        switch (e.key.toLowerCase()) {
                            case 'b':
                                e.preventDefault();
                                insertMarkdown('bold');
                                break;
                            case 'i':
                                e.preventDefault();
                                insertMarkdown('italic');
                                break;
                            case 'k':
                                e.preventDefault();
                                insertMarkdown('link');
                                break;
                            case 'h':
                                e.preventDefault();
                                insertMarkdown('heading');
                                break;
                        }
                    }
                });
            });

            // Generate random key (8 chars, alphanumeric)
            function generateKey() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let key = '';
                for (let i = 0; i < 8; i++) {
                    key += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return key;
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

            // Encrypt content
            async function encryptContent(content, password) {
                const encoder = new TextEncoder();
                const key = await deriveKey(password);

                const iv = crypto.getRandomValues(new Uint8Array(12));
                const encrypted = await crypto.subtle.encrypt(
                    { name: 'AES-GCM', iv: iv },
                    key,
                    encoder.encode(content)
                );

                // Combine IV + encrypted data and encode as base64
                const combined = new Uint8Array(iv.length + encrypted.byteLength);
                combined.set(iv);
                combined.set(new Uint8Array(encrypted), iv.length);

                return btoa(String.fromCharCode(...combined));
            }

            // Share secret
            async function shareSecret() {
                const content = document.getElementById('content').value.trim();
                const btn = document.getElementById('share-btn');
                const btnText = document.getElementById('share-btn-text');
                const errorDiv = document.getElementById('error-message');
                const errorText = document.getElementById('error-text');

                // Get optional features
                const requiresConfirmation = document.getElementById('requires-confirmation').checked;
                const enablePassword = document.getElementById('enable-password').checked;
                const password = document.getElementById('secret-password').value;

                if (!content) {
                    errorDiv.classList.remove('hidden');
                    errorText.textContent = 'Please enter some content to share.';
                    return;
                }

                // Validate password if enabled
                if (enablePassword && password.length < 4) {
                    errorDiv.classList.remove('hidden');
                    errorText.textContent = 'Password must be at least 4 characters long.';
                    document.getElementById('secret-password').focus();
                    return;
                }

                errorDiv.classList.add('hidden');
                btn.disabled = true;
                btnText.textContent = 'Encrypting...';

                try {
                    // Generate encryption key
                    const encryptionKey = generateKey();

                    // Encrypt the content
                    const encryptedContent = await encryptContent(content, encryptionKey);

                    btnText.textContent = 'Saving...';

                    // Build request payload
                    const payload = {
                        content: encryptedContent,
                        requires_confirmation: requiresConfirmation,
                    };

                    if (enablePassword && password) {
                        payload.password = password;
                    }

                    // Send to server
                    const response = await fetch('{{ route("secrets.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Failed to save secret');
                    }

                    const data = await response.json();

                    // Build the shareable link
                    const baseUrl = window.location.origin;
                    const secretLink = `${baseUrl}/${data.id}#${encryptionKey}`;

                    // Show success view with features info
                    document.getElementById('secret-link').value = secretLink;
                    showSuccessFeatures(requiresConfirmation, enablePassword);
                    document.getElementById('editor-view').classList.add('hidden');
                    document.getElementById('success-view').classList.remove('hidden');

                } catch (error) {
                    console.error('Error:', error);
                    errorDiv.classList.remove('hidden');
                    errorText.textContent = error.message || 'Failed to create secret. Please try again.';
                } finally {
                    btn.disabled = false;
                    btnText.textContent = 'Share Secret';
                }
            }

            // Show enabled features in success view
            function showSuccessFeatures(confirmation, password) {
                const featuresDiv = document.getElementById('enabled-features');
                const features = [];

                if (confirmation) {
                    features.push(`
                        <div class="flex items-center gap-2 text-slate-300">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Confirmation required</span>
                        </div>
                    `);
                }

                if (password) {
                    features.push(`
                        <div class="flex items-center gap-2 text-slate-300">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Password protected</span>
                        </div>
                    `);
                }

                if (features.length > 0) {
                    featuresDiv.innerHTML = features.join('');
                    featuresDiv.classList.remove('hidden');
                } else {
                    featuresDiv.classList.add('hidden');
                }
            }

            // Copy link to clipboard
            function copyLink() {
                const link = document.getElementById('secret-link');
                const btnText = document.getElementById('copy-btn-text');

                navigator.clipboard.writeText(link.value).then(() => {
                    btnText.textContent = 'Copied!';
                    setTimeout(() => {
                        btnText.textContent = 'Copy';
                    }, 2000);
                });
            }

            // Reset form for new secret
            function resetForm() {
                document.getElementById('content').value = '';
                document.getElementById('preview').innerHTML = '<p class="text-slate-600 italic">Preview will appear here...</p>';
                document.getElementById('requires-confirmation').checked = false;
                document.getElementById('enable-password').checked = false;
                document.getElementById('secret-password').value = '';
                document.getElementById('password-field').classList.add('hidden');
                document.getElementById('enabled-features').classList.add('hidden');
                document.getElementById('editor-view').classList.remove('hidden');
                document.getElementById('success-view').classList.add('hidden');
            }
        </script>
    </body>
</html>
