<div class="fi-wi-widget col-span-full">
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 shadow-sm">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2080%2080%27%20fill%3D%27none%27%3E%3Ccircle%20cx%3D%2740%27%20cy%3D%2740%27%20r%3D%2740%27%20fill%3D%27rgba%28255%2C255%2C255%2C0.05%29%27%2F%3E%3C%2Fsvg%3E')] opacity-40"></div>

        <div class="relative flex items-center justify-between px-6 py-5 sm:px-8">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                    <x-heroicon-o-hand-raised class="h-7 w-7 text-white" />
                </div>
                <div>
                    <p class="text-lg font-bold text-white">
                        {{ $salutation }}, {{ $userName }}!
                    </p>
                    <p class="mt-0.5 text-sm text-teal-100">
                        @if ($faskesNama)
                            {{ $faskesTipe === 'puskesmas' ? 'Puskesmas' : 'Pustu' }} {{ $faskesNama }} &mdash;
                        @endif
                        Selamat datang di panel manajemen Ruang Obat
                    </p>
                </div>
            </div>

            <div class="hidden items-center gap-6 sm:flex">
                <div class="text-center">
                    <p class="text-2xl font-bold text-white">{{ number_format($totalObat) }}</p>
                    <p class="text-xs text-teal-100">Total Obat</p>
                </div>
                <div class="h-10 w-px bg-white/20"></div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-white">{{ number_format($totalFaskes) }}</p>
                    <p class="text-xs text-teal-100">Fasilitas</p>
                </div>
            </div>
        </div>
    </div>
</div>