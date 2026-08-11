<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panduan RUANG OBAT') - {{ config('app.name', 'RUANG OBAT') }}</title>
    <script>
        (function() {
            const stored = localStorage.getItem('panduan-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored || 'system';
            const isDark = theme === 'dark' || (theme === 'system' && prefersDark);
            if (isDark) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/css/panduan.css', 'resources/js/panduan.js'])
    @stack('head')
</head>
<body class="min-h-full bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 antialiased">

    <header class="bg-gradient-to-r from-[#0083BF] to-[#0D7773] text-white sticky top-0 z-40 shadow-sm">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="{{ route('panduan.index') }}" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 bg-white/15 rounded-lg flex items-center justify-center group-hover:bg-white/25 transition-colors">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="font-bold text-lg tracking-tight">RUANG<span class="text-white/80">OBAT</span></span>
                    </a>

                    <nav class="hidden md:flex items-center gap-1">
                        <a href="{{ url('/') }}" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-md transition-colors">
                            Beranda
                        </a>
                        <a href="{{ route('panduan.index') }}" class="px-4 py-2 text-sm font-medium text-white bg-white/15 border-b-2 border-white/80 rounded-t-md transition-colors">
                            Panduan
                        </a>
                        <a href="#" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-md transition-colors">
                            API
                        </a>
                        <a href="#" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-md transition-colors">
                            Dukungan
                        </a>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <button id="sidebar-toggle" type="button" class="lg:hidden p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-md transition-colors" aria-label="Buka menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>

                    <div class="relative">
                        <button id="theme-toggle" type="button" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-md transition-colors" aria-label="Toggle tema">
                            <svg data-theme-icon-light class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                            </svg>
                            <svg data-theme-icon-dark class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                            </svg>
                            <svg data-theme-icon-system class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                            </svg>
                        </button>
                        <div id="theme-menu" class="hidden absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 dark:ring-white/10 z-50 py-1">
                            <button data-theme-set="light" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                                <span>Terang</span>
                                <svg data-theme-check="light" class="w-4 h-4 ml-auto text-primary-600 dark:text-primary-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                            <button data-theme-set="dark" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                                <span>Gelap</span>
                                <svg data-theme-check="dark" class="w-4 h-4 ml-auto text-primary-600 dark:text-primary-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                            <button data-theme-set="system" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                                <span>Sistem</span>
                                <svg data-theme-check="system" class="w-4 h-4 ml-auto text-primary-600 dark:text-primary-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-white/10 cursor-pointer transition-colors">
                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-xs font-semibold">
                            RO
                        </div>
                        <span class="text-sm font-medium">RUANG OBAT</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex items-start">
        @hasSection('sidebar')
            @yield('sidebar')
        @endif

        <main class="flex-1 min-w-0 lg:ml-72">
            @yield('content')
        </main>

        @hasSection('toc')
            @yield('toc')
        @endif
    </div>

    <div id="mobile-backdrop" class="mobile-backdrop"></div>

    <footer class="lg:ml-72 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 mt-12">
        <div class="px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <p>© {{ date('Y') }} {{ config('app.name', 'RUANG OBAT') }}. Dokumentasi internal.</p>
                <a href="{{ route('panduan.index') }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">
                    Kembali ke Daftar Panduan
                </a>
            </div>
        </div>
    </footer>
</body>
</html>
