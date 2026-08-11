@props([
    'sections' => [],
    'activeSlug' => null,
])

<aside id="panduan-sidebar"
       class="panduan-sidebar fixed inset-y-0 left-0 z-40 -translate-x-full lg:translate-x-0 lg:bottom-auto lg:top-16 transition-transform duration-200 ease-in-out">

    <div class="lg:hidden flex items-center justify-between px-4 h-16 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <span class="font-semibold text-gray-900 dark:text-white">Menu Panduan</span>
        <button id="sidebar-close" type="button" class="p-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md" aria-label="Tutup menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="sticky top-0 z-10 p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <input
                id="sidebar-search"
                type="search"
                placeholder="Cari halaman ini..."
                class="search-input"
                autocomplete="off"
            >
        </div>
    </div>

    <nav class="py-2">
        <div class="px-4 py-2">
            <a href="{{ route('panduan.index') }}"
               class="flex items-center gap-2 text-sm font-semibold {{ $activeSlug === null ? 'text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300 hover:text-primary-700 dark:hover:text-primary-300' }} transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                Beranda Panduan
            </a>
        </div>

        @foreach($sections as $section)
            <div class="mt-2" data-search-section>
                <button type="button"
                        data-section-toggle
                        aria-expanded="true"
                        class="sidebar-section-toggle">
                    <span>{{ $section['judul'] }}</span>
                    <svg data-chevron class="w-3.5 h-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
                <div data-section-body class="pb-2">
                    @foreach($section['children'] as $child)
                        <a href="{{ route('panduan.show', $child['slug']) }}"
                           data-search-item
                           class="sidebar-link {{ $activeSlug === $child['slug'] ? 'active' : '' }}">
                            <span data-search-match="{{ $child['judul'] }}"
                                  data-search-title="{{ $child['judul'] }}">{{ $child['judul'] }}</span>
                            <span data-search-desc="{{ $child['deskripsi'] ?? '' }}"
                                  class="hidden">{{ $child['deskripsi'] ?? '' }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div id="sidebar-empty" class="hidden px-4 py-8 text-center">
            <svg class="mx-auto w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Coba kata kunci lain</p>
        </div>
    </nav>
</aside>
