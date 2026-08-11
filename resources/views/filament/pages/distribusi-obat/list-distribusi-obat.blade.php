<x-filament-panels::page>
    @if ($isDinasUser)
        <div class="ruang-obat-tab-container">
            <button wire:click="filterByTab('dinas')" type="button"
                class="ruang-obat-tab-button
                    {{ $activeTab === 'dinas' ? 'ruang-obat-tab-active' : 'ruang-obat-tab-inactive' }}">
                Distribusi Dinas
                <span class="ruang-obat-tab-button-badge
                    {{ $activeTab === 'dinas' ? 'ruang-obat-tab-badge-active' : 'ruang-obat-tab-badge-inactive' }}">
                    {{ $tabCounts['dinas'] ?? 0 }}
                </span>
            </button>
            <button wire:click="filterByTab('puskesmas')" type="button"
                class="ruang-obat-tab-button
                    {{ $activeTab === 'puskesmas' ? 'ruang-obat-tab-active' : 'ruang-obat-tab-inactive' }}">
                Distribusi Puskesmas
                <span class="ruang-obat-tab-button-badge
                    {{ $activeTab === 'puskesmas' ? 'ruang-obat-tab-badge-active' : 'ruang-obat-tab-badge-inactive' }}">
                    {{ $tabCounts['puskesmas'] ?? 0 }}
                </span>
            </button>
        </div>
    @endif

    <div class="mt-2.5 mb-4">
        {{ $this->filtersForm }}
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
