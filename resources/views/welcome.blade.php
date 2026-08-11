<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Ruang Obat — Sistem Informasi Manajemen Obat untuk Fasilitas Kesehatan. Kelola inventaris, distribusi, dan prediksi kebutuhan obat secara cerdas.">
        <title>{{ config('app.name', 'RUANG OBAT') }} — Sistem Informasi Manajemen Obat</title>

        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('theme');
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var theme = stored || (prefersDark ? 'dark' : 'light');
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    document.documentElement.setAttribute('data-theme', theme);
                } catch (e) {}
            })();
        </script>

        @fonts
        @vite(['resources/css/app.css'])

        <style>
            [x-cloak] { display: none !important; }

            @keyframes fade-in-up {
                from { opacity: 0; transform: translateY(32px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes fade-in {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes fade-in-down {
                from { opacity: 0; transform: translateY(-16px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes scale-in {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
            @keyframes slide-in-left {
                from { opacity: 0; transform: translateX(-40px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes slide-in-right {
                from { opacity: 0; transform: translateX(40px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes float-slow {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-20px) rotate(2deg); }
            }
            @keyframes float-slower {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-14px) rotate(-1.5deg); }
            }
            @keyframes pulse-glow {
                0%, 100% { opacity: 0.4; }
                50% { opacity: 0.8; }
            }
            @keyframes gradient-shift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }

            .animate-on-scroll {
                opacity: 0;
                transform: translateY(32px);
                transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .animate-on-scroll.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
            .animate-on-scroll.delay-100 { transition-delay: 0.1s; }
            .animate-on-scroll.delay-200 { transition-delay: 0.2s; }
            .animate-on-scroll.delay-300 { transition-delay: 0.3s; }
            .animate-on-scroll.delay-400 { transition-delay: 0.4s; }
            .animate-on-scroll.delay-500 { transition-delay: 0.5s; }
            .animate-on-scroll.delay-600 { transition-delay: 0.6s; }

            .hero-gradient-dark {
                background: linear-gradient(135deg, #0083BF 0%, #0D7773 50%, #067D9B 100%);
                background-size: 100% 100%;
                animation: gradient-shift 8s ease infinite;
            }
            .hero-gradient-light {
                background: linear-gradient(135deg, #0083BF 0%, #0D7773 50%, #067D9B 100%);
                background-size: 100% 100%;
                animation: gradient-shift 8s ease infinite;
            }

            .card-glow:hover {
                box-shadow: 0 0 0 1px rgba(6, 125, 155, 0.3), 0 8px 40px -8px rgba(6, 125, 155, 0.2);
            }
            .dark .card-glow:hover {
                box-shadow: 0 0 0 1px rgba(6, 125, 155, 0.3), 0 8px 40px -8px rgba(6, 125, 155, 0.2);
            }
            html:not(.dark) .card-glow:hover {
                box-shadow: 0 0 0 1px rgba(6, 125, 155, 0.2), 0 8px 40px -8px rgba(6, 125, 155, 0.15);
            }

            .hero-shape-1 { animation: float-slow 7s ease-in-out infinite; }
            .hero-shape-2 { animation: float-slower 9s ease-in-out infinite; }
            .hero-shape-3 { animation: float-slow 11s ease-in-out infinite reverse; }
            .hero-shape-pulse { animation: pulse-glow 4s ease-in-out infinite; }

            .navbar-blur {
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
            }

            html { scroll-behavior: smooth; }
        </style>
    </head>
    <body class="min-h-screen bg-white text-gray-900 antialiased transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">

        {{-- NAVBAR --}}
        <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    {{-- Logo --}}
                    <a href="/" class="flex items-center gap-3 group">
                        <img src="{{ asset('assets/images/logo-light.svg') }}" alt="Logo" class="h-8 w-8 transition-transform group-hover:scale-110 dark:hidden">
                        <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="Logo" class="hidden h-8 w-8 transition-transform group-hover:scale-110 dark:block">
                        <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ config('app.name', 'RUANG OBAT') }}
                        </span>
                    </a>

                    {{-- Nav Links --}}
                    <div class="hidden sm:flex items-center gap-3">
                        <a href="/panduan" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                            Panduan
                        </a>

                        {{-- Theme Toggle --}}
                        <button type="button" id="theme-toggle" aria-label="Toggle theme"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white">
                            {{-- Sun icon (shown in dark mode = click to go light) --}}
                            <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                            {{-- Moon icon (shown in light mode = click to go dark) --}}
                            <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                        </button>

                        <a href="{{ auth()->check() ? '/admin' : '/login' }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-primary-700 active:scale-[0.97] dark:bg-white/10 dark:text-white dark:hover:bg-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                            Masuk
                        </a>
                    </div>

                    {{-- Mobile menu button --}}
                    <div class="flex items-center gap-1 sm:hidden">
                        <button type="button" id="theme-toggle-mobile" aria-label="Toggle theme"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white">
                            <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                            <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                        </button>
                        <button type="button" id="mobile-menu-btn" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile menu --}}
            <div id="mobile-menu" class="hidden border-t border-gray-200 bg-white/80 sm:hidden navbar-blur dark:border-white/10 dark:bg-gray-950/80">
                <div class="px-4 py-4 space-y-3">
                    <a href="/panduan" class="block py-2 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Panduan</a>
                    <a href="{{ auth()->check() ? '/admin' : '/login' }}" class="flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition-all hover:bg-primary-700 dark:bg-white/10 dark:hover:bg-white/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        Masuk
                    </a>
                </div>
            </div>
        </nav>

        {{-- HERO SECTION --}}
        <section class="hero-gradient-light dark:hero-gradient-dark relative flex min-h-screen items-center justify-center overflow-hidden pt-16">
            {{-- Decorative shapes --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="hero-shape-1 absolute -top-24 -right-24 h-96 w-96 rounded-full bg-white/30 dark:bg-white/5"></div>
                <div class="hero-shape-2 absolute top-1/3 -left-32 h-80 w-80 rounded-full bg-white/30 dark:bg-white/5"></div>
                <div class="hero-shape-3 absolute bottom-16 right-1/4 h-64 w-64 rounded-full bg-white/30 dark:bg-white/5"></div>
                <div class="hero-shape-pulse absolute top-1/2 left-1/2 h-[500px] w-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/20 dark:bg-white/[0.03]"></div>

                {{-- Grid pattern --}}
                <svg class="absolute inset-0 h-full w-full opacity-[0.06] dark:opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="hero-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                            <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#hero-grid)" />
                </svg>
            </div>

            <div class="relative z-10 mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 lg:px-8">
                {{-- Badge --}}
                <div class="mb-8 inline-flex animate-on-scroll items-center gap-2 rounded-full border border-white/30 bg-white/20 px-4 py-1.5 text-sm font-medium text-white backdrop-blur-sm dark:border-white/20 dark:bg-white/10 dark:text-white/90">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-300 dark:bg-emerald-400"></span>
                    Sistem Manajemen Obat Terintegrasi
                </div>

                {{-- Headline --}}
                <h1 class="mx-auto max-w-4xl animate-on-scroll text-4xl font-extrabold tracking-tight text-white delay-100 sm:text-5xl lg:text-6xl xl:text-7xl">
                    Manajemen Obat
                    <span class="mt-2 block bg-gradient-to-r from-emerald-200 via-teal-100 to-cyan-200 bg-clip-text text-transparent dark:from-emerald-300 dark:via-teal-200 dark:to-cyan-300">
                        Cerdas & Terpadu
                    </span>
                </h1>

                {{-- Subtitle --}}
                <p class="mx-auto mt-6 max-w-2xl animate-on-scroll text-lg text-white/85 delay-200 sm:text-xl dark:text-white/70">
                    Kelola inventaris obat untuk puskesmas dan pustu dengan pelacakan batch, distribusi multi-level, serta prediksi kebutuhan berbasis AI.
                </p>

                {{-- CTA Buttons --}}
                <div class="mt-10 flex animate-on-scroll flex-col items-center justify-center gap-4 delay-300 sm:flex-row">
                    <a href="{{ auth()->check() ? '/admin' : '/login' }}"
                       class="group inline-flex items-center gap-2.5 rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-primary-700 shadow-lg shadow-black/10 transition-all hover:bg-gray-50 hover:shadow-xl hover:shadow-black/15 active:scale-[0.97]">
                        Masuk ke Dashboard
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="/panduan"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/40 px-7 py-3.5 text-sm font-semibold text-white transition-all hover:border-white/60 hover:bg-white/15 active:scale-[0.97] dark:border-white/25 dark:hover:border-white/40 dark:hover:bg-white/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        Pelajari Lebih Lanjut
                    </a>
                </div>

                {{-- Stats row --}}
                <div class="mx-auto mt-16 grid max-w-lg animate-on-scroll grid-cols-3 gap-8 delay-400">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white sm:text-3xl">25+</div>
                        <div class="mt-1 text-xs text-white/70 sm:text-sm dark:text-white/50">Modul Fitur</div>
                    </div>
                    <div class="border-x border-white/30 text-center dark:border-white/15">
                        <div class="text-2xl font-bold text-white sm:text-3xl">FEFO</div>
                        <div class="mt-1 text-xs text-white/70 sm:text-sm dark:text-white/50">Metode Stok</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white sm:text-3xl">AI</div>
                        <div class="mt-1 text-xs text-white/70 sm:text-sm dark:text-white/50">Prediksi Kebutuhan</div>
                    </div>
                </div>
            </div>

            {{-- Scroll indicator --}}
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
                <svg class="h-6 w-6 text-white/50 dark:text-white/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                </svg>
            </div>
        </section>

        {{-- FITUR SECTION --}}
        <section class="relative bg-gray-50 py-24 sm:py-32 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                {{-- Section header --}}
                <div class="mx-auto mb-16 max-w-2xl text-center sm:mb-20">
                    <p class="animate-on-scroll text-sm font-semibold uppercase tracking-widest text-primary-600 dark:text-primary-400">Fitur Utama</p>
                    <h2 class="mt-3 animate-on-scroll text-3xl font-extrabold tracking-tight text-gray-900 delay-100 sm:text-4xl lg:text-5xl dark:text-white">
                        Semua yang Anda Butuhkan
                    </h2>
                    <p class="mt-4 animate-on-scroll text-lg text-gray-600 delay-200 dark:text-gray-400">
                        Platform lengkap untuk mengelola obat dari penerimaan hingga distribusi ke pasien.
                    </p>
                </div>

                {{-- Feature grid --}}
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- 1. Manajemen Stok --}}
                    <div class="group relative rounded-2xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:border-gray-300 hover:bg-gray-50 card-glow animate-on-scroll delay-100 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:bg-gray-800/50">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500/10 to-primary-700/10 ring-1 ring-primary-500/20 transition-all group-hover:scale-110 group-hover:ring-primary-500/40 dark:from-[#067D9B]/20 dark:to-[#0D7773]/20 dark:ring-[#067D9B]/30 dark:group-hover:ring-[#067D9B]/50">
                            <svg class="h-6 w-6 text-primary-600 dark:text-[#067D9B]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manajemen Stok</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Pelacakan stok berbasis batch nomor & kedaluwarsa. Mendukung metode FEFO, FIFO, dan LIFO sesuai kebutuhan fasilitas kesehatan Anda.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-200 dark:bg-[#067D9B]/10 dark:text-[#067D9B] dark:ring-[#067D9B]/20">Batch</span>
                            <span class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-200 dark:bg-[#067D9B]/10 dark:text-[#067D9B] dark:ring-[#067D9B]/20">FEFO</span>
                            <span class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-200 dark:bg-[#067D9B]/10 dark:text-[#067D9B] dark:ring-[#067D9B]/20">Stok Opname</span>
                        </div>
                    </div>

                    {{-- 2. Prediksi AI --}}
                    <div class="group relative rounded-2xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:border-gray-300 hover:bg-gray-50 card-glow animate-on-scroll delay-200 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:bg-gray-800/50">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 ring-1 ring-emerald-500/20 transition-all group-hover:scale-110 group-hover:ring-emerald-500/40 dark:from-emerald-500/20 dark:to-teal-500/20 dark:ring-emerald-500/30 dark:group-hover:ring-emerald-500/50">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Prediksi Kebutuhan AI</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Mesin prediksi berbasis Rubix ML dengan Gradient Boost dan Random Forest untuk memprediksi kebutuhan obat secara akurat.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">Rubix ML</span>
                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">Moving Average</span>
                        </div>
                    </div>

                    {{-- 3. Distribusi & Permintaan --}}
                    <div class="group relative rounded-2xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:border-gray-300 hover:bg-gray-50 card-glow animate-on-scroll delay-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:bg-gray-800/50">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500/10 to-purple-500/10 ring-1 ring-violet-500/20 transition-all group-hover:scale-110 group-hover:ring-violet-500/40 dark:from-violet-500/20 dark:to-purple-500/20 dark:ring-violet-500/30 dark:group-hover:ring-violet-500/50">
                            <svg class="h-6 w-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Distribusi & Permintaan</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Alur permintaan dan distribusi obat multi-level dari pustu ke puskesmas hingga ke dinas kesehatan dengan faktur otomatis.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-md bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-inset ring-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">Multi-Level</span>
                            <span class="inline-flex items-center rounded-md bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-inset ring-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">Faktur</span>
                        </div>
                    </div>

                    {{-- 4. Pelaporan --}}
                    <div class="group relative rounded-2xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:border-gray-300 hover:bg-gray-50 card-glow animate-on-scroll delay-200 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:bg-gray-800/50">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 ring-1 ring-amber-500/20 transition-all group-hover:scale-110 group-hover:ring-amber-500/40 dark:from-amber-500/20 dark:to-orange-500/20 dark:ring-amber-500/30 dark:group-hover:ring-amber-500/50">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pelaporan Lengkap</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Generate LPLPO, RKO, Neraca Tahunan, dan Faktur dalam format PDF dan Excel. Semua laporan yang dibutuhkan dalam satu platform.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">PDF</span>
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">Excel</span>
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">LPLPO</span>
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">RKO</span>
                        </div>
                    </div>

                    {{-- 5. Multi-Level --}}
                    <div class="group relative rounded-2xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:border-gray-300 hover:bg-gray-50 card-glow animate-on-scroll delay-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:bg-gray-800/50">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500/10 to-sky-500/10 ring-1 ring-cyan-500/20 transition-all group-hover:scale-110 group-hover:ring-cyan-500/40 dark:from-cyan-500/20 dark:to-sky-500/20 dark:ring-cyan-500/30 dark:group-hover:ring-cyan-500/50">
                            <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hierarki Fasilitas</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Struktur hierarki puskesmas sebagai induk dan pustu sebagai sub-unit. Setiap unit memiliki stok dan akses tersendiri.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 ring-1 ring-inset ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:ring-cyan-500/20">Puskesmas</span>
                            <span class="inline-flex items-center rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 ring-1 ring-inset ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:ring-cyan-500/20">Pustu</span>
                            <span class="inline-flex items-center rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 ring-1 ring-inset ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:ring-cyan-500/20">Dinas</span>
                        </div>
                    </div>

                    {{-- 6. Keamanan --}}
                    <div class="group relative rounded-2xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:border-gray-300 hover:bg-gray-50 card-glow animate-on-scroll delay-400 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:bg-gray-800/50">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500/10 to-pink-500/10 ring-1 ring-rose-500/20 transition-all group-hover:scale-110 group-hover:ring-rose-500/40 dark:from-rose-500/20 dark:to-pink-500/20 dark:ring-rose-500/30 dark:group-hover:ring-rose-500/50">
                            <svg class="h-6 w-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Keamanan & Akses</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            Role-based access control dengan 5 level pengguna: Super Admin, Admin Gudang, Admin Dinas, Puskesmas, dan Pustu.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20">RBAC</span>
                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20">Audit Log</span>
                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20">Google OAuth</span>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- UGM SECTION --}}
        <section class="relative border-y border-gray-200 bg-white py-24 sm:py-32 dark:border-gray-800/50 dark:bg-gray-900/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-16">

                    {{-- Logo --}}
                    <div class="flex-shrink-0 animate-on-scroll">
                        <div class="relative">
                            <div class="absolute -inset-4 rounded-2xl bg-gradient-to-br from-primary-500/10 to-primary-700/10 blur-xl dark:from-[#067D9B]/10 dark:to-[#0D7773]/10"></div>
                            <div class="relative flex h-36 w-36 items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 sm:h-44 sm:w-44 sm:p-8 dark:border-gray-700/50 dark:bg-gray-800/50">
                                <img src="{{ asset('assets/images/logo-ugm.png') }}" alt="Logo Universitas Gadjah Mada" class="h-full w-full object-contain">
                            </div>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="text-center lg:text-left">
                        <p class="animate-on-scroll text-sm font-semibold uppercase tracking-widest text-primary-600 dark:text-primary-400">Mitra Pengembangan</p>
                        <h2 class="mt-3 animate-on-scroll text-2xl font-extrabold tracking-tight text-gray-900 delay-100 sm:text-3xl dark:text-white">
                            Universitas Gadjah Mada
                        </h2>
                        <p class="mt-4 max-w-xl animate-on-scroll text-base leading-relaxed text-gray-600 delay-200 dark:text-gray-400">
                            Universitas Gadjah Mada (UGM) adalah universitas negeri tertua dan terbesar di Indonesia yang didirikan pada tahun 1949 di Yogyakarta. Sebagai universitas riset terkemuka, UGM berkontribusi aktif dalam pengembangan teknologi untuk sektor kesehatan nasional.
                        </p>

                        {{-- Faculties --}}
                        <div class="mt-6 flex flex-wrap justify-center gap-3 animate-on-scroll delay-300 lg:justify-start">
                            <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700/50 dark:bg-gray-800/50">
                                <svg class="h-4 w-4 flex-shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5m-4.25-11.396c.251.023.501.05.75.082M12 21a8.966 8.966 0 005.982-2.275M12 21a8.966 8.966 0 01-5.982-2.275M15.75 3.186a24.284 24.284 0 012.091.477M15.75 3.186c-.777.195-1.531.424-2.25.683m0 0a24.3 24.3 0 00-4.5 0m6.75 0v5.714a2.25 2.25 0 01-.659 1.591L12 16.832m3.485-13.646a24.284 24.284 0 00-2.091.477m0 0c-.777.195-1.531.424-2.25.683" />
                                </svg>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fakultas Farmasi</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700/50 dark:bg-gray-800/50">
                                <svg class="h-4 w-4 flex-shrink-0 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">FK-KMK</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700/50 dark:bg-gray-800/50">
                                <svg class="h-4 w-4 flex-shrink-0 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fakultas Kesehatan Masyarakat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA SECTION --}}
        <section class="relative overflow-hidden py-24 sm:py-32">
            {{-- Background gradient --}}
            <div class="hero-gradient-light dark:hero-gradient-dark absolute inset-0 opacity-95"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent dark:from-gray-950/30 dark:to-transparent"></div>

            {{-- Decorative shapes --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="hero-shape-2 absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-white/30 dark:bg-white/5"></div>
                <div class="hero-shape-1 absolute -top-16 -right-16 h-56 w-56 rounded-full bg-white/30 dark:bg-white/5"></div>
            </div>

            <div class="relative z-10 mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="animate-on-scroll text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Siap Mengelola Obat
                    <br class="hidden sm:block">
                    dengan Lebih Baik?
                </h2>
                <p class="mx-auto mt-5 max-w-xl animate-on-scroll text-lg text-white/90 delay-100 dark:text-white/70">
                    Mulai gunakan Ruang Obat untuk mengoptimalkan manajemen inventaris obat di fasilitas kesehatan Anda.
                </p>
                <div class="mt-10 flex animate-on-scroll flex-col items-center justify-center gap-4 delay-200 sm:flex-row">
                    <a href="{{ auth()->check() ? '/admin' : '/login' }}"
                       class="group inline-flex items-center gap-2.5 rounded-xl bg-white px-8 py-4 text-base font-bold text-primary-700 shadow-xl shadow-black/10 transition-all hover:bg-gray-50 hover:shadow-2xl hover:shadow-black/15 active:scale-[0.97]">
                        Masuk Sekarang
                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="/panduan"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/50 px-8 py-4 text-base font-semibold text-white transition-all hover:border-white/70 hover:bg-white/15 active:scale-[0.97] dark:border-white/30 dark:hover:border-white/50 dark:hover:bg-white/10">
                        Lihat Panduan
                    </a>
                </div>
            </div>
        </section>

        {{-- FOOTER --}}
        <footer class="border-t border-gray-200 bg-gray-50 py-10 dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/images/logo-light.svg') }}" alt="Logo" class="h-6 w-6 dark:hidden">
                        <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="Logo" class="hidden h-6 w-6 dark:block">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ config('app.name', 'RUANG OBAT') }}</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <a href="/panduan" class="text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Panduan</a>
                        <a href="{{ auth()->check() ? '/admin' : '/login' }}" class="text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Masuk</a>
                    </div>
                    <p class="text-xs text-gray-500">
                        &copy; {{ date('Y') }} {{ config('app.name', 'RUANG OBAT') }}. Hak cipta dilindungi.
                    </p>
                </div>
            </div>
        </footer>

        <script>
            (function () {
                // --- Scroll animations ---
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

                document.querySelectorAll('.animate-on-scroll').forEach(function (el) {
                    observer.observe(el);
                });

                // --- Navbar scroll effect ---
                var navbar = document.getElementById('navbar');
                function onScroll() {
                    var y = window.scrollY;
                    if (y > 80) {
                        if (document.documentElement.classList.contains('dark')) {
                            navbar.classList.add('navbar-blur', 'bg-gray-950/80', 'shadow-lg', 'shadow-black/10', 'border-b', 'border-white/5');
                            navbar.classList.remove('bg-white/80', 'border-gray-200');
                        } else {
                            navbar.classList.add('navbar-blur', 'bg-white/80', 'shadow-lg', 'shadow-gray-900/5', 'border-b', 'border-gray-200');
                            navbar.classList.remove('bg-gray-950/80', 'border-white/5', 'shadow-black/10');
                        }
                    } else {
                        navbar.classList.remove(
                            'navbar-blur',
                            'bg-gray-950/80', 'bg-white/80',
                            'shadow-lg', 'shadow-black/10', 'shadow-gray-900/5',
                            'border-b', 'border-white/5', 'border-gray-200'
                        );
                    }
                }

                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();

                // --- Mobile menu toggle ---
                var menuBtn = document.getElementById('mobile-menu-btn');
                var mobileMenu = document.getElementById('mobile-menu');
                if (menuBtn && mobileMenu) {
                    menuBtn.addEventListener('click', function () {
                        mobileMenu.classList.toggle('hidden');
                    });
                }

                // --- Theme toggle ---
                function setTheme(theme) {
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    document.documentElement.setAttribute('data-theme', theme);
                    try { localStorage.setItem('theme', theme); } catch (e) {}
                    onScroll();
                }

                function toggleTheme() {
                    var current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                    setTheme(current === 'dark' ? 'light' : 'dark');
                }

                var toggleBtn = document.getElementById('theme-toggle');
                var toggleMobileBtn = document.getElementById('theme-toggle-mobile');
                if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
                if (toggleMobileBtn) toggleMobileBtn.addEventListener('click', toggleTheme);

                // Sync if system preference changes (only when user hasn't chosen)
                if (window.matchMedia) {
                    var mq = window.matchMedia('(prefers-color-scheme: dark)');
                    var mqHandler = function (e) {
                        try {
                            if (!localStorage.getItem('theme')) {
                                setTheme(e.matches ? 'dark' : 'light');
                            }
                        } catch (err) {}
                    };
                    if (mq.addEventListener) mq.addEventListener('change', mqHandler);
                    else if (mq.addListener) mq.addListener(mqHandler);
                }
            })();
        </script>
    </body>
</html>
