<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Setup Awal Aplikasi RUANG OBAT - Sistem Informasi Manajemen Obat">
    <title>Setup Awal - {{ config('app.name', 'RUANG OBAT') }}</title>

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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">

    {{-- NAVBAR --}}
    <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="/" class="flex items-center gap-3 group">
                    <img src="{{ asset('assets/images/logo-light.svg') }}" alt="Logo" class="h-8 w-8 transition-transform group-hover:scale-110 dark:hidden">
                    <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="Logo" class="hidden h-8 w-8 transition-transform group-hover:scale-110 dark:block">
                    <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ config('app.name', 'RUANG OBAT') }}
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <a href="/" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        Beranda
                    </a>
                    <a href="/panduan" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        Panduan
                    </a>

                    <button type="button" id="theme-toggle" aria-label="Toggle theme"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white">
                        <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                        <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    {{-- HERO SECTION --}}
    <section class="hero-gradient-light dark:hero-gradient-dark relative min-h-[40vh] overflow-hidden pt-16 pb-16">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="hero-shape-1 absolute -top-24 -right-24 h-96 w-96 rounded-full bg-white/30 dark:bg-white/5"></div>
            <div class="hero-shape-2 absolute top-1/3 -left-32 h-80 w-80 rounded-full bg-white/30 dark:bg-white/5"></div>
            <div class="hero-shape-pulse absolute top-1/2 left-1/2 h-[500px] w-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/20 dark:bg-white/[0.03]"></div>

            <svg class="absolute inset-0 h-full w-full opacity-[0.06] dark:opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="hero-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hero-grid)" />
            </svg>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <div class="mb-6 inline-flex animate-on-scroll items-center gap-2 rounded-full border border-white/30 bg-white/20 px-4 py-1.5 text-sm font-medium text-white backdrop-blur-sm dark:border-white/20 dark:bg-white/10 dark:text-white/90">
                <span class="flex h-2 w-2 rounded-full bg-emerald-300 dark:bg-emerald-400"></span>
                Setup Awal Aplikasi
            </div>

            <h1 class="mx-auto max-w-4xl animate-on-scroll text-3xl font-extrabold tracking-tight text-white delay-100 sm:text-4xl lg:text-5xl">
                Konfigurasi
                <span class="mt-2 block bg-gradient-to-r from-emerald-200 via-teal-100 to-cyan-200 bg-clip-text text-transparent dark:from-emerald-300 dark:via-teal-200 dark:to-cyan-300">
                    RUANG OBAT
                </span>
            </h1>

            <p class="mx-auto mt-4 max-w-2xl animate-on-scroll text-base text-white/85 delay-200 sm:text-lg dark:text-white/70">
                Ikuti langkah-langkah berikut untuk mengkonfigurasi aplikasi sesuai dengan kebutuhan organisasi Anda.
            </p>
        </div>
    </section>

    {{-- SETUP WIZARD SECTION --}}
    <section class="relative -mt-16 pb-24">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8" x-data="setupWizard({{ session('currentStep', $errors->any() ? 3 : 0) }})" x-init="init()">

            {{-- Flash Messages --}}
            @if(session('error'))
                <div class="mb-6 animate-on-scroll rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                        </div>
                        <button @click="show = false" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Progress Steps Card --}}
            <div class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700/50 dark:bg-gray-800/50">
                <div class="px-8 py-8 sm:px-10">
                    <div class="flex items-center justify-center">
                        <template x-for="(step, index) in steps" :key="index">
                            <div class="flex items-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full border-2 text-sm font-bold shadow-sm transition-all duration-300"
                                         :class="{
                                             'border-primary-500 bg-primary-500 text-white shadow-primary-500/30 dark:border-[#067D9B] dark:bg-[#067D9B] dark:shadow-[#067D9B]/30': currentStep === index,
                                             'border-emerald-500 bg-emerald-500 text-white shadow-emerald-500/30 dark:border-emerald-400 dark:bg-emerald-400 dark:shadow-emerald-400/30': currentStep > index,
                                             'border-gray-300 bg-white text-gray-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400': currentStep < index
                                         }">
                                        <span x-show="currentStep <= index" x-text="index + 1"></span>
                                        <svg x-show="currentStep > index" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="mt-2.5 text-xs font-semibold tracking-wide transition-colors duration-300"
                                          :class="{
                                              'text-primary-600 dark:text-[#067D9B]': currentStep === index,
                                              'text-emerald-600 dark:text-emerald-400': currentStep > index,
                                              'text-gray-500 dark:text-gray-400': currentStep < index
                                          }"
                                          x-text="step.title"></span>
                                </div>
                                <div class="mx-3 h-1 w-12 rounded-full transition-all duration-500 sm:w-24"
                                     :class="{
                                         'bg-primary-500 dark:bg-[#067D9B]': currentStep > index,
                                         'bg-gray-200 dark:bg-gray-700': currentStep <= index
                                     }"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form x-ref="form" method="POST" action="{{ route('setup-wizard.store') }}" class="space-y-8">
                @csrf

                {{-- Step 1: Super Admin --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 dark:border-gray-700/50 dark:bg-gray-800/50 sm:p-10 animate-on-scroll" x-show="currentStep === 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500/10 to-primary-700/10 ring-1 ring-primary-500/20 dark:from-[#067D9B]/20 dark:to-[#0D7773]/20 dark:ring-[#067D9B]/30">
                            <svg class="h-5 w-5 text-primary-600 dark:text-[#067D9B]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Super Admin</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Akun administrator utama sistem</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="superadmin_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Superadmin <span class="text-red-500">*</span></label>
                            <input type="text" id="superadmin_name" name="superadmin_name" value="{{ old('superadmin_name') }}" required placeholder="Contoh: Budi Santoso"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.superadminName ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.superadminName">
                            <p x-show="errors.superadminName" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.superadminName"></p>
                            @error('superadmin_name') <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="superadmin_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email Superadmin <span class="text-red-500">*</span></label>
                            <input type="email" id="superadmin_email" name="superadmin_email" value="{{ old('superadmin_email') }}" required placeholder="admin@dinkes.go.id"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.superadminEmail ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.superadminEmail">
                            <p x-show="errors.superadminEmail" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.superadminEmail"></p>
                            @error('superadmin_email') <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                                <button type="button" @click="generateSuperadminPassword()" class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-[#067D9B] dark:hover:text-[#056A86]">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                                    Generate
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required minlength="8" placeholder="Min 8 karakter"
                                    class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500 pr-10"
                                    :class="errors.password ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                    x-model="form.password">
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <p x-show="errors.password" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.password"></p>
                            @error('password') <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p> @enderror

                            {{-- Password Strength --}}
                            <div class="mt-2.5" x-show="form.password.length > 0" x-transition>
                                <div class="flex gap-1.5">
                                    <template x-for="i in 4" :key="i">
                                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="passwordStrength >= i ? strengthColors[passwordStrength - 1] : 'bg-gray-200 dark:bg-gray-700'"></div>
                                    </template>
                                </div>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span class="text-xs font-semibold" :class="strengthTextColors[passwordStrength - 1]" x-text="strengthLabels[passwordStrength - 1]"></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="strengthDesc[passwordStrength - 1]"></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="showPasswordConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi password"
                                    class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500 pr-10"
                                    :class="errors.passwordConfirm ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                    x-model="form.passwordConfirm">
                                <button type="button" @click="showPasswordConfirm = !showPasswordConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="showPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <p x-show="errors.passwordConfirm" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.passwordConfirm"></p>
                            <p x-show="form.passwordConfirm && form.password !== form.passwordConfirm && !errors.passwordConfirm" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400">Password tidak cocok</p>
                            @error('password_confirmation') <p class="mt-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Step 2: Admin Dinas & Gudang --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 dark:border-gray-700/50 dark:bg-gray-800/50 sm:p-10 animate-on-scroll" x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    {{-- Admin Dinas --}}
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 ring-1 ring-emerald-500/20 dark:from-emerald-500/20 dark:to-teal-500/20 dark:ring-emerald-500/30">
                            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Admin Dinas</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Akun untuk pengelola dari Dinas Kesehatan</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="admin_dinas_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Admin Dinas <span class="text-red-500">*</span></label>
                            <input type="text" id="admin_dinas_name" name="admin_dinas_name" value="{{ old('admin_dinas_name') }}" required placeholder="Contoh: Admin Dinas"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.adminDinasName ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.adminDinasName">
                            <p x-show="errors.adminDinasName" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminDinasName"></p>
                        </div>
                        <div>
                            <label for="admin_dinas_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email Admin Dinas <span class="text-red-500">*</span></label>
                            <input type="email" id="admin_dinas_email" name="admin_dinas_email" value="{{ old('admin_dinas_email') }}" required placeholder="admindinas@dinkes.go.id"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.adminDinasEmail ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.adminDinasEmail">
                            <p x-show="errors.adminDinasEmail" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminDinasEmail"></p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="admin_dinas_password" class="text-sm font-medium text-gray-700 dark:text-gray-300">Password Admin Dinas <span class="text-red-500">*</span></label>
                                <button type="button" @click="generateAdminDinasPassword()" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                                    Generate
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showAdminDinasPassword ? 'text' : 'password'" id="admin_dinas_password" name="admin_dinas_password" required minlength="8" placeholder="Min 8 karakter"
                                    class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500 pr-10"
                                    :class="errors.adminDinasPassword ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                    x-model="form.adminDinasPassword">
                                <button type="button" @click="showAdminDinasPassword = !showAdminDinasPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showAdminDinasPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="showAdminDinasPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <p x-show="errors.adminDinasPassword" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminDinasPassword"></p>

                            {{-- Password Strength --}}
                            <div class="mt-2.5" x-show="form.adminDinasPassword.length > 0" x-transition>
                                <div class="flex gap-1.5">
                                    <template x-for="i in 4" :key="i">
                                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="adminDinasPasswordStrength >= i ? strengthColors[adminDinasPasswordStrength - 1] : 'bg-gray-200 dark:bg-gray-700'"></div>
                                    </template>
                                </div>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span class="text-xs font-semibold" :class="strengthTextColors[adminDinasPasswordStrength - 1]" x-text="strengthLabels[adminDinasPasswordStrength - 1]"></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="strengthDesc[adminDinasPasswordStrength - 1]"></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="admin_dinas_password_confirm" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="showAdminDinasPasswordConfirm ? 'text' : 'password'" id="admin_dinas_password_confirm" name="admin_dinas_password_confirm" required placeholder="Ulangi password"
                                    class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500 pr-10"
                                    :class="errors.adminDinasPasswordConfirm ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                    x-model="form.adminDinasPasswordConfirm">
                                <button type="button" @click="showAdminDinasPasswordConfirm = !showAdminDinasPasswordConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showAdminDinasPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="showAdminDinasPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <p x-show="errors.adminDinasPasswordConfirm" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminDinasPasswordConfirm"></p>
                        </div>
                    </div>

                    <div class="my-6 border-t border-gray-200 dark:border-gray-700"></div>

                    {{-- Admin Gudang --}}
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500/10 to-purple-500/10 ring-1 ring-violet-500/20 dark:from-violet-500/20 dark:to-purple-500/20 dark:ring-violet-500/30">
                            <svg class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Admin Gudang</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Akun untuk pengelola Gudang Farmasi</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="admin_gudang_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Admin Gudang <span class="text-red-500">*</span></label>
                            <input type="text" id="admin_gudang_name" name="admin_gudang_name" value="{{ old('admin_gudang_name') }}" required placeholder="Contoh: Admin Gudang"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.adminGudangName ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.adminGudangName">
                            <p x-show="errors.adminGudangName" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminGudangName"></p>
                        </div>
                        <div>
                            <label for="admin_gudang_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email Admin Gudang <span class="text-red-500">*</span></label>
                            <input type="email" id="admin_gudang_email" name="admin_gudang_email" value="{{ old('admin_gudang_email') }}" required placeholder="admingudang@dinkes.go.id"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.adminGudangEmail ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.adminGudangEmail">
                            <p x-show="errors.adminGudangEmail" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminGudangEmail"></p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="admin_gudang_password" class="text-sm font-medium text-gray-700 dark:text-gray-300">Password Admin Gudang <span class="text-red-500">*</span></label>
                                <button type="button" @click="generateAdminGudangPassword()" class="inline-flex items-center gap-1 text-xs font-medium text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                                    Generate
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showAdminGudangPassword ? 'text' : 'password'" id="admin_gudang_password" name="admin_gudang_password" required minlength="8" placeholder="Min 8 karakter"
                                    class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500 pr-10"
                                    :class="errors.adminGudangPassword ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                    x-model="form.adminGudangPassword">
                                <button type="button" @click="showAdminGudangPassword = !showAdminGudangPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showAdminGudangPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="showAdminGudangPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <p x-show="errors.adminGudangPassword" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminGudangPassword"></p>

                            {{-- Password Strength --}}
                            <div class="mt-2.5" x-show="form.adminGudangPassword.length > 0" x-transition>
                                <div class="flex gap-1.5">
                                    <template x-for="i in 4" :key="i">
                                        <div class="h-1.5 flex-1 rounded-full transition-all duration-300" :class="adminGudangPasswordStrength >= i ? strengthColors[adminGudangPasswordStrength - 1] : 'bg-gray-200 dark:bg-gray-700'"></div>
                                    </template>
                                </div>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span class="text-xs font-semibold" :class="strengthTextColors[adminGudangPasswordStrength - 1]" x-text="strengthLabels[adminGudangPasswordStrength - 1]"></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="strengthDesc[adminGudangPasswordStrength - 1]"></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="admin_gudang_password_confirm" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="showAdminGudangPasswordConfirm ? 'text' : 'password'" id="admin_gudang_password_confirm" name="admin_gudang_password_confirm" required placeholder="Ulangi password"
                                    class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500 pr-10"
                                    :class="errors.adminGudangPasswordConfirm ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                    x-model="form.adminGudangPasswordConfirm">
                                <button type="button" @click="showAdminGudangPasswordConfirm = !showAdminGudangPasswordConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showAdminGudangPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="showAdminGudangPasswordConfirm" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <p x-show="errors.adminGudangPasswordConfirm" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.adminGudangPasswordConfirm"></p>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Organization --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 dark:border-gray-700/50 dark:bg-gray-800/50 sm:p-10 animate-on-scroll" x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 ring-1 ring-amber-500/20 dark:from-amber-500/20 dark:to-orange-500/20 dark:ring-amber-500/30">
                            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Informasi Organisasi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Data dasar Dinas Kesehatan Anda</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="organization_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Dinas Kesehatan <span class="text-red-500">*</span></label>
                            <input type="text" id="organization_name" name="organization_name" value="{{ old('organization_name') }}" required placeholder="Contoh: Dinas Kesehatan Kota Bandung"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.organizationName ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.organizationName">
                            <p x-show="errors.organizationName" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.organizationName"></p>
                        </div>
                        <div>
                            <label for="organization_code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kode Dinas <span class="text-red-500">*</span></label>
                            <input type="text" id="organization_code" name="organization_code" value="{{ old('organization_code') }}" required placeholder="Contoh: DINKES-BDG"
                                class="w-full rounded-lg border bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:ring-2 focus:outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                :class="errors.organizationCode ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-400' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20'"
                                x-model="form.organizationCode">
                            <p x-show="errors.organizationCode" x-cloak class="mt-1.5 text-xs text-red-500 dark:text-red-400" x-text="errors.organizationCode"></p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="organization_description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi (Opsional)</label>
                        <textarea id="organization_description" name="organization_description" rows="3" placeholder="Deskripsi singkat tentang organisasi" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-[#067D9B] dark:focus:ring-[#067D9B]/20 placeholder:text-gray-400 dark:placeholder:text-gray-500">{{ old('organization_description') }}</textarea>
                    </div>

                    {{-- Info Box --}}
                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Penting!</p>
                                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                    Setelah menyelesaikan setup, sistem akan membuat akun-akun yang telah dikonfigurasi. Pastikan semua data sudah benar.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 4: Confirmation --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 dark:border-gray-700/50 dark:bg-gray-800/50 sm:p-10 animate-on-scroll" x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500/10 to-pink-500/10 ring-1 ring-rose-500/20 dark:from-rose-500/20 dark:to-pink-500/20 dark:ring-rose-500/30">
                            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Konfirmasi Setup</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tinjau data sebelum menyelesaikan setup</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Super Admin --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Super Admin</h4>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Nama</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.superadminName || '-'"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Email</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.superadminEmail || '-'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Admin Dinas --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Admin Dinas</h4>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Nama</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.adminDinasName || '-'"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Email</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.adminDinasEmail || '-'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Admin Gudang --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Admin Gudang</h4>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Nama</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.adminGudangName || '-'"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Email</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.adminGudangEmail || '-'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Organization --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Organisasi</h4>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Nama Dinas</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.organizationName || '-'"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Kode Dinas</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.organizationCode || '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Master Data Info --}}
                    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Master Data yang Akan Di-seed:</h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span>5 roles (Super Admin, Admin Gudang, Admin Dinas, Puskesmas, Pustu)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span>~57 obat FORNAS standar</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span>Preset avatar boy & girl</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Navigation Buttons --}}
                <div class="flex items-center justify-between pt-4">
                    <button type="button" x-show="currentStep > 0" @click="prevStep()" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        Kembali
                    </button>
                    <div x-show="currentStep === 0"></div>

                    <button type="button" x-show="currentStep < 3" @click="nextStep()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:bg-primary-700 hover:shadow-xl active:scale-[0.97] dark:bg-[#067D9B] dark:hover:bg-[#056A86]">
                        Lanjut
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>

                    <button type="submit" x-show="currentStep === 3" @click.prevent="submitForm()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:bg-primary-700 hover:shadow-xl active:scale-[0.97] dark:bg-[#067D9B] dark:hover:bg-[#056A86]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Selesaikan Setup
                    </button>
                </div>
            </form>

            {{-- Footer Info --}}
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Setup ini hanya perlu dilakukan sekali saat pertama kali menggunakan aplikasi.
                </p>
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
                <p class="text-xs text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name', 'RUANG OBAT') }}. Hak cipta dilindungi.
                </p>
            </div>
        </div>
    </footer>

    <script>
        function setupWizard(initialStep = 0) {
            return {
                currentStep: initialStep,
                steps: [
                    { title: 'Super Admin' },
                    { title: 'Admin' },
                    { title: 'Organisasi' },
                    { title: 'Konfirmasi' }
                ],
                showPassword: false,
                showPasswordConfirm: false,
                showAdminDinasPassword: false,
                showAdminDinasPasswordConfirm: false,
                showAdminGudangPassword: false,
                showAdminGudangPasswordConfirm: false,
                form: {
                    superadminName: '{{ old("superadmin_name") }}',
                    superadminEmail: '{{ old("superadmin_email") }}',
                    password: '',
                    passwordConfirm: '',
                    adminDinasName: '{{ old("admin_dinas_name") }}',
                    adminDinasEmail: '{{ old("admin_dinas_email") }}',
                    adminDinasPassword: '',
                    adminDinasPasswordConfirm: '',
                    adminGudangName: '{{ old("admin_gudang_name") }}',
                    adminGudangEmail: '{{ old("admin_gudang_email") }}',
                    adminGudangPassword: '',
                    adminGudangPasswordConfirm: ''
                },
                errors: {},
                strengthLabels: ['Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'],
                strengthColors: ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-emerald-500'],
                strengthTextColors: ['text-red-500', 'text-orange-500', 'text-yellow-600', 'text-emerald-500'],
                strengthDesc: [
                    'Gunakan kombinasi huruf besar, kecil, angka, dan simbol',
                    'Cukup lemah. Tambahkan angka dan simbol',
                    'Kuat. Pertahankan!',
                    'Sangat kuat! Password ini sangat aman'
                ],
                showPassword: false,
                showPasswordConfirm: false,
                showAdminDinasPassword: false,
                showAdminDinasPasswordConfirm: false,
                showAdminGudangPassword: false,
                showAdminGudangPasswordConfirm: false,

                get passwordStrength() {
                    const password = this.form.password;
                    if (!password) return 0;
                    let strength = 0;
                    if (password.length >= 8) strength++;
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                    if (/\d/.test(password)) strength++;
                    if (/[^a-zA-Z0-9]/.test(password)) strength++;
                    return strength;
                },

                get adminDinasPasswordStrength() {
                    const password = this.form.adminDinasPassword;
                    if (!password) return 0;
                    let strength = 0;
                    if (password.length >= 8) strength++;
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                    if (/\d/.test(password)) strength++;
                    if (/[^a-zA-Z0-9]/.test(password)) strength++;
                    return strength;
                },

                get adminGudangPasswordStrength() {
                    const password = this.form.adminGudangPassword;
                    if (!password) return 0;
                    let strength = 0;
                    if (password.length >= 8) strength++;
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                    if (/\d/.test(password)) strength++;
                    if (/[^a-zA-Z0-9]/.test(password)) strength++;
                    return strength;
                },

                isValidEmail(email) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                },

                generatePassword() {
                    const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    const lower = 'abcdefghijklmnopqrstuvwxyz';
                    const numbers = '0123456789';
                    const symbols = '!@#$%^&*';
                    const allChars = upper + lower + numbers + symbols;

                    let password = '';
                    // Ensure at least one of each type
                    password += upper[Math.floor(Math.random() * upper.length)];
                    password += lower[Math.floor(Math.random() * lower.length)];
                    password += numbers[Math.floor(Math.random() * numbers.length)];
                    password += symbols[Math.floor(Math.random() * symbols.length)];

                    // Fill remaining 12 characters randomly
                    const array = new Uint8Array(12);
                    crypto.getRandomValues(array);
                    for (let i = 0; i < 12; i++) {
                        password += allChars[array[i] % allChars.length];
                    }

                    // Shuffle the password
                    return password.split('').sort(() => Math.random() - 0.5).join('');
                },

                generateSuperadminPassword() {
                    this.form.password = this.generatePassword();
                    this.form.passwordConfirm = this.form.password;
                },

                generateAdminDinasPassword() {
                    this.form.adminDinasPassword = this.generatePassword();
                    this.form.adminDinasPasswordConfirm = this.form.adminDinasPassword;
                },

                generateAdminGudangPassword() {
                    this.form.adminGudangPassword = this.generatePassword();
                    this.form.adminGudangPasswordConfirm = this.form.adminGudangPassword;
                },

                validateStep() {
                    this.errors = {};
                    let isValid = true;

                    if (this.currentStep === 0) {
                        if (!this.form.superadminName.trim()) {
                            this.errors.superadminName = 'Nama superadmin wajib diisi.';
                            isValid = false;
                        }
                        if (!this.form.superadminEmail.trim()) {
                            this.errors.superadminEmail = 'Email superadmin wajib diisi.';
                            isValid = false;
                        } else if (!this.isValidEmail(this.form.superadminEmail)) {
                            this.errors.superadminEmail = 'Format email tidak valid.';
                            isValid = false;
                        }
                        if (!this.form.password) {
                            this.errors.password = 'Password wajib diisi.';
                            isValid = false;
                        } else if (this.form.password.length < 8) {
                            this.errors.password = 'Password minimal 8 karakter.';
                            isValid = false;
                        } else if (this.passwordStrength < 4) {
                            this.errors.password = 'Password harus sangat kuat: huruf besar, kecil, angka, dan simbol (!@#$%^&*).';
                            isValid = false;
                        }
                        if (!this.form.passwordConfirm) {
                            this.errors.passwordConfirm = 'Konfirmasi password wajib diisi.';
                            isValid = false;
                        } else if (this.form.password !== this.form.passwordConfirm) {
                            this.errors.passwordConfirm = 'Password tidak cocok.';
                            isValid = false;
                        }
                    }

                    if (this.currentStep === 1) {
                        if (!this.form.adminDinasName.trim()) {
                            this.errors.adminDinasName = 'Nama admin dinas wajib diisi.';
                            isValid = false;
                        }
                        if (!this.form.adminDinasEmail.trim()) {
                            this.errors.adminDinasEmail = 'Email admin dinas wajib diisi.';
                            isValid = false;
                        } else if (!this.isValidEmail(this.form.adminDinasEmail)) {
                            this.errors.adminDinasEmail = 'Format email tidak valid.';
                            isValid = false;
                        } else if (this.form.adminDinasEmail === this.form.superadminEmail) {
                            this.errors.adminDinasEmail = 'Email admin dinas tidak boleh sama dengan email superadmin.';
                            isValid = false;
                        }
                        if (!this.form.adminDinasPassword) {
                            this.errors.adminDinasPassword = 'Password admin dinas wajib diisi.';
                            isValid = false;
                        } else if (this.form.adminDinasPassword.length < 8) {
                            this.errors.adminDinasPassword = 'Password minimal 8 karakter.';
                            isValid = false;
                        } else if (this.adminDinasPasswordStrength < 4) {
                            this.errors.adminDinasPassword = 'Password harus sangat kuat: huruf besar, kecil, angka, dan simbol.';
                            isValid = false;
                        }
                        if (!this.form.adminDinasPasswordConfirm) {
                            this.errors.adminDinasPasswordConfirm = 'Konfirmasi password wajib diisi.';
                            isValid = false;
                        } else if (this.form.adminDinasPassword !== this.form.adminDinasPasswordConfirm) {
                            this.errors.adminDinasPasswordConfirm = 'Password tidak cocok.';
                            isValid = false;
                        }

                        if (!this.form.adminGudangName.trim()) {
                            this.errors.adminGudangName = 'Nama admin gudang wajib diisi.';
                            isValid = false;
                        }
                        if (!this.form.adminGudangEmail.trim()) {
                            this.errors.adminGudangEmail = 'Email admin gudang wajib diisi.';
                            isValid = false;
                        } else if (!this.isValidEmail(this.form.adminGudangEmail)) {
                            this.errors.adminGudangEmail = 'Format email tidak valid.';
                            isValid = false;
                        } else if (this.form.adminGudangEmail === this.form.superadminEmail) {
                            this.errors.adminGudangEmail = 'Email admin gudang tidak boleh sama dengan email superadmin.';
                            isValid = false;
                        } else if (this.form.adminGudangEmail === this.form.adminDinasEmail) {
                            this.errors.adminGudangEmail = 'Email admin gudang tidak boleh sama dengan email admin dinas.';
                            isValid = false;
                        }
                        if (!this.form.adminGudangPassword) {
                            this.errors.adminGudangPassword = 'Password admin gudang wajib diisi.';
                            isValid = false;
                        } else if (this.form.adminGudangPassword.length < 8) {
                            this.errors.adminGudangPassword = 'Password minimal 8 karakter.';
                            isValid = false;
                        } else if (this.adminGudangPasswordStrength < 4) {
                            this.errors.adminGudangPassword = 'Password harus sangat kuat: huruf besar, kecil, angka, dan simbol.';
                            isValid = false;
                        }
                        if (!this.form.adminGudangPasswordConfirm) {
                            this.errors.adminGudangPasswordConfirm = 'Konfirmasi password wajib diisi.';
                            isValid = false;
                        } else if (this.form.adminGudangPassword !== this.form.adminGudangPasswordConfirm) {
                            this.errors.adminGudangPasswordConfirm = 'Password tidak cocok.';
                            isValid = false;
                        }
                    }

                    if (this.currentStep === 2) {
                        if (!this.form.organizationName.trim()) {
                            this.errors.organizationName = 'Nama dinas kesehatan wajib diisi.';
                            isValid = false;
                        }
                        if (!this.form.organizationCode.trim()) {
                            this.errors.organizationCode = 'Kode dinas wajib diisi.';
                            isValid = false;
                        }
                    }

                    return isValid;
                },

                init() {
                    // Scroll animations
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

                    document.querySelectorAll('.animate-on-scroll').forEach(el => {
                        observer.observe(el);
                    });
                },

                nextStep() {
                    if (this.validateStep()) {
                        this.currentStep++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        this.$nextTick(() => {
                            const firstError = document.querySelector('.border-red-500');
                            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                    }
                },

                prevStep() {
                    if (this.currentStep > 0) {
                        this.currentStep--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                submitForm() {
                    if (this.validateStep()) {
                        this.$refs.form.submit();
                    } else {
                        this.$nextTick(() => {
                            const firstError = document.querySelector('.border-red-500');
                            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                    }
                }
            }
        }

        // Standalone IIFE - theme toggle & navbar (terpisah dari Alpine.js)
        (function () {
            // Navbar scroll effect
            const navbar = document.getElementById('navbar');
            const onScroll = () => {
                if (!navbar) return;
                const y = window.scrollY;
                if (y > 80) {
                    if (document.documentElement.classList.contains('dark')) {
                        navbar.classList.add('navbar-blur', 'bg-gray-950/80', 'shadow-lg', 'shadow-black/10', 'border-b', 'border-white/5');
                        navbar.classList.remove('bg-white/80', 'border-gray-200');
                    } else {
                        navbar.classList.add('navbar-blur', 'bg-white/80', 'shadow-lg', 'shadow-gray-900/5', 'border-b', 'border-gray-200');
                        navbar.classList.remove('bg-gray-950/80', 'border-white/5', 'shadow-black/10');
                    }
                } else {
                    navbar.classList.remove('navbar-blur', 'bg-gray-950/80', 'bg-white/80', 'shadow-lg', 'shadow-black/10', 'shadow-gray-900/5', 'border-b', 'border-white/5', 'border-gray-200');
                }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            // Theme toggle
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
                const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                setTheme(current === 'dark' ? 'light' : 'dark');
            }

            var toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);

            // System preference listener
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
