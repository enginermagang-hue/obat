<x-filament-panels::page>
    <div
        class="h-[calc(100vh-12rem)]"
        x-data="{ collapsed: $wire.entangle('settingsCollapsed') }"
    >
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex flex-col h-full overflow-hidden">

            {{-- Header (full width): Preview PDF title + action buttons --}}
            <div class="fi-section-header flex items-center gap-2 px-4 py-3 border-b border-gray-950/5 dark:border-white/10 shrink-0">
                <span class="fi-section-header-heading text-sm font-medium text-gray-950 dark:text-white">Preview PDF</span>
                <div class="ml-auto flex items-center gap-2">
                    <x-filament::button
                        size="xs"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        wire:click="refreshPreview"
                    >
                        Refresh
                    </x-filament::button>
                    <x-filament::button
                        size="xs"
                        color="primary"
                        icon="heroicon-o-arrow-down-tray"
                        wire:click="download"
                    >
                        Download
                    </x-filament::button>
                </div>
            </div>

            {{-- Body: PDF Preview + Settings Panel (side by side) --}}
            <div class="flex flex-1 min-h-0">

                {{-- === PDF Preview (left) === --}}
                <div class="flex-1 min-w-0 relative overflow-hidden bg-gray-100 dark:bg-gray-800">
                    <div id="pdf-loading-overlay" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 dark:bg-gray-900/80 rounded transition-opacity duration-300">
                        <svg class="animate-spin h-10 w-10 text-primary-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Memuat preview…</span>
                    </div>
                    <iframe
                        id="pdf-preview"
                        src="{{ $this->previewUrl }}"
                        class="w-full h-full rounded border-0"
                        style="min-height: 400px;"
                    ></iframe>
                </div>

                {{-- === Toggle Tab (always visible) === --}}
                <button
                    type="button"
                    class="shrink-0 w-6 flex items-center justify-center bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors z-10"
                    @click="collapsed = !collapsed"
                    :title="collapsed ? 'Tampilkan pengaturan' : 'Sembunyikan pengaturan'"
                >
                    <svg
                        class="w-4 h-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': collapsed }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                {{-- === Settings Panel (collapsible horizontal) === --}}
                <div
                    class="transition-all duration-200 ease-in-out overflow-hidden shrink-0 border-l border-gray-200 dark:border-gray-700"
                    :class="collapsed ? 'w-0 opacity-0' : 'w-[350px] opacity-100'"
                >
                    <div class="w-[350px] h-full flex flex-col bg-white dark:bg-gray-900 overflow-y-auto">
                        {{-- Settings header --}}
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                            <x-filament::icon
                                icon="heroicon-o-cog-6-tooth"
                                class="w-4 h-4 text-gray-400"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pengaturan Cetak</span>
                        </div>

                        {{-- Settings form --}}
                        <div class="p-4">
                            {{ $this->form }}
                        </div>

                        {{-- Action buttons --}}
                        <div class="mt-auto p-4 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex flex-col gap-2">
                                <x-filament::button
                                    color="primary"
                                    icon="heroicon-o-arrow-down-tray"
                                    wire:click="download"
                                    class="w-full"
                                >
                                    Download
                                </x-filament::button>
                                <x-filament::button
                                    color="gray"
                                    icon="heroicon-o-bookmark"
                                    wire:click="saveDefault"
                                    class="w-full"
                                >
                                    Simpan Default
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function() {
            const iframe = document.getElementById('pdf-preview');
            const overlay = document.getElementById('pdf-loading-overlay');

            function showOverlay() {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            }

            function hideOverlay() {
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }

            function isLoaded() {
                return iframe.contentDocument && iframe.contentDocument.readyState === 'complete';
            }

            iframe.addEventListener('load', hideOverlay);

            new MutationObserver(function() {
                showOverlay();
            }).observe(iframe, {
                attributes: true,
                attributeFilter: ['src'],
            });

            if (isLoaded()) {
                hideOverlay();
            } else {
                showOverlay();
            }
        })();
    </script>
    @endpush
</x-filament-panels::page>
