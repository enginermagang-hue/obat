<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<script>
    (function () {
        var t = localStorage.getItem('login-theme');
        var d = t === 'light' ? false : t === 'dark' ? true : window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (d) document.documentElement.classList.add('dark');
        else document.documentElement.classList.remove('dark');
    })();
</script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('app.name', 'RUANG OBAT') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.7s ease-out both;
        }
        .animate-fade-in {
            animation: fade-in 0.8s ease-out both;
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        #particles-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950">
    <div class="flex min-h-screen">

        {{-- LEFT PANEL: Branding --}}
        <div class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-[#0083BF] to-[#0D7773] lg:flex lg:flex-col lg:items-center lg:justify-center">
            <canvas id="particles-canvas"></canvas>

            <div class="relative z-10 flex flex-col items-center px-12 text-center animate-fade-in">
                {{-- Logo --}}
                <div class="mb-8 animate-float">
                    <img src="{{ asset('assets/images/logo.svg') }}" alt="Logo" class="h-28 w-28 drop-shadow-lg dark:hidden">
                    <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="Logo" class="h-28 w-28 drop-shadow-lg hidden dark:block">
                </div>

                {{-- Brand Name --}}
                <h1 class="text-4xl font-extrabold tracking-tight text-white drop-shadow-md">
                    {{ config('app.name', 'RUANG OBAT') }}
                </h1>

                {{-- Tagline --}}
                <p class="mt-4 max-w-xs text-lg font-medium text-white/85">
                    Sistem Informasi Manajemen Obat
                </p>
                <p class="mt-2 max-w-sm text-sm text-white/60">
                    Kelola inventaris obat dengan cerdas untuk fasilitas kesehatan Anda
                </p>

                {{-- Feature highlights --}}
                <div class="mt-10 grid grid-cols-3 gap-6 text-white/90">
                    <div class="flex flex-col items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 backdrop-blur-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium">Aman</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 backdrop-blur-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium">Cepat</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 backdrop-blur-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium">AI-Powered</span>
                    </div>
                </div>
            </div>

            {{-- Bottom copyright --}}
            <p class="absolute bottom-6 left-0 right-0 text-center text-xs text-white/50">
                &copy; {{ date('Y') }} {{ config('app.name', 'RUANG OBAT') }}. All rights reserved.
            </p>
        </div>

        {{-- RIGHT PANEL: Login Form --}}
        <div class="relative flex flex-1 flex-col items-center justify-center px-6 py-12 sm:px-12 lg:px-16">
            {{-- Back to Landing --}}
            <a
                href="{{ url('/') }}"
                class="absolute top-4 left-4 inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-200/60 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Beranda
            </a>
            {{-- Dark/Light Toggle --}}
            <button
                type="button"
                id="theme-toggle"
                class="absolute top-4 right-4 flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-200/60 dark:text-gray-400 dark:hover:bg-gray-700/60 transition-colors"
                title="Ganti tema"
            >
                <svg id="icon-moon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg id="icon-sun" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
            {{-- Mobile logo --}}
            <div class="mb-8 flex flex-col items-center text-center lg:hidden animate-fade-in-up">
                <img src="{{ asset('assets/images/logo.svg') }}" alt="Logo" class="mb-4 h-16 w-16 dark:hidden">
                <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="Logo" class="mb-4 h-16 w-16 hidden dark:block">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ config('app.name', 'RUANG OBAT') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sistem Informasi Manajemen Obat</p>
            </div>

            {{-- Form Card --}}
            <div class="w-full max-w-md animate-fade-in-up delay-100">
                <div class="hidden lg:block mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Selamat datang</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masuk ke akun Anda untuk melanjutkan</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 animate-fade-in-up delay-100">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-[#0D7773] focus:ring-2 focus:ring-[#0D7773]/20 focus:outline-none transition"
                            placeholder="email@contoh.com"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 pr-10 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-[#0D7773] focus:ring-2 focus:ring-[#0D7773]/20 focus:outline-none transition"
                                placeholder="Masukkan password"
                            >
                            <button
                                type="button"
                                id="toggle-password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                tabindex="-1"
                            >
                                <svg id="eye-open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eye-closed" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                class="peer sr-only"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span id="toggle-pill" class="relative h-5 w-9 rounded-full bg-gray-300 transition-colors duration-200 dark:bg-gray-600">
                                <span id="toggle-dot" class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full flex justify-center rounded-lg bg-[#0D7773] px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-[#0D7773]/25 hover:bg-[#0f8c87] hover:shadow-md hover:shadow-[#0D7773]/30 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#0D7773] focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200"
                    >
                        Masuk
                    </button>
                </form>

                @if (config('services.google.client_id'))
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="bg-gray-50 dark:bg-gray-950 px-3 text-gray-500 dark:text-gray-400">atau</span>
                            </div>
                        </div>

                        <a
                            href="{{ route('socialite.filament.admin.oauth.redirect', 'google') }}"
                            class="mt-4 flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#0D7773]/20 transition-all duration-200 active:scale-[0.98]"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Masuk dengan Google
                        </a>
                    </div>
                @endif
            </div>

            {{-- Mobile copyright --}}
            <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-500 lg:hidden">
                &copy; {{ date('Y') }} {{ config('app.name', 'RUANG OBAT') }}. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        (function () {
            const canvas = document.getElementById('particles-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let particles = [];
            let w, h;

            function resize() {
                const rect = canvas.parentElement.getBoundingClientRect();
                w = canvas.width = rect.width;
                h = canvas.height = rect.height;
            }

            function createParticle() {
                return {
                    x: Math.random() * w,
                    y: Math.random() * h,
                    r: Math.random() * 3 + 1,
                    dx: (Math.random() - 0.5) * 0.4,
                    dy: (Math.random() - 0.5) * 0.3,
                    opacity: Math.random() * 0.3 + 0.1,
                };
            }

            function init() {
                resize();
                const count = Math.floor((w * h) / 8000);
                particles = [];
                for (let i = 0; i < count; i++) {
                    particles.push(createParticle());
                }
            }

            function draw() {
                ctx.clearRect(0, 0, w, h);
                for (const p of particles) {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255,255,255,' + p.opacity + ')';
                    ctx.fill();

                    p.x += p.dx;
                    p.y += p.dy;

                    if (p.x < -10) p.x = w + 10;
                    if (p.x > w + 10) p.x = -10;
                    if (p.y < -10) p.y = h + 10;
                    if (p.y > h + 10) p.y = -10;
                }
                requestAnimationFrame(draw);
            }

            window.addEventListener('resize', function () {
                resize();
            });

            init();
            draw();
        })();
    </script>

    <script>
        (function () {
            var toggle = document.getElementById('toggle-password');
            var input = document.getElementById('password');
            var eyeOpen = document.getElementById('eye-open');
            var eyeClosed = document.getElementById('eye-closed');
            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    var isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    eyeOpen.classList.toggle('hidden', isPassword);
                    eyeClosed.classList.toggle('hidden', !isPassword);
                });
            }

            var checkbox = document.getElementById('remember');
            var pill = document.getElementById('toggle-pill');
            var dot = document.getElementById('toggle-dot');
            if (checkbox && pill && dot) {
                function syncToggle() {
                    var on = checkbox.checked;
                    pill.classList.toggle('bg-[#0D7773]', on);
                    pill.classList.toggle('dark:bg-[#0D7773]', on);
                    pill.classList.toggle('bg-gray-300', !on);
                    pill.classList.toggle('dark:bg-gray-600', !on);
                    dot.style.transform = on ? 'translateX(18px)' : 'translateX(0)';
                }
                checkbox.addEventListener('change', syncToggle);
                syncToggle();
            }

            var themeBtn = document.getElementById('theme-toggle');
            var moonIcon = document.getElementById('icon-moon');
            var sunIcon = document.getElementById('icon-sun');
            if (themeBtn) {
                function syncThemeIcon() {
                    var isDark = document.documentElement.classList.contains('dark');
                    moonIcon.classList.toggle('hidden', isDark);
                    sunIcon.classList.toggle('hidden', !isDark);
                }
                syncThemeIcon();
                themeBtn.addEventListener('click', function () {
                    var isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('login-theme', isDark ? 'dark' : 'light');
                    syncThemeIcon();
                });
            }
        })();
    </script>
</body>
</html>
