<x-filament-panels::page>
    @if ($isDinasUser)
        <div class="ruang-obat-tab-container">
            <button wire:click="filterByTab('dinas')" type="button" class="ruang-obat-tab-button
                                {{ $activeTab === 'dinas' ? 'ruang-obat-tab-active' : 'ruang-obat-tab-inactive' }}">
                Penerimaan Dinas
                <span
                    class="ruang-obat-tab-button-badge
                                {{ $activeTab === 'dinas' ? 'ruang-obat-tab-badge-active' : 'ruang-obat-tab-badge-inactive' }}">
                    {{ $penerimaanDinasCount }}
                </span>
            </button>
            <button wire:click="filterByTab('faskes')" type="button" class="ruang-obat-tab-button
                                {{ $activeTab === 'faskes' ? 'ruang-obat-tab-active' : 'ruang-obat-tab-inactive' }}">
                Penerimaan Faskes
                <span
                    class="ruang-obat-tab-button-badge
                                {{ $activeTab === 'faskes' ? 'ruang-obat-tab-badge-active' : 'ruang-obat-tab-badge-inactive' }}">
                    {{ $penerimaanFaskesCount }}
                </span>
            </button>
        </div>
    @endif

    <div class="my-4">
        {{ $this->filtersForm }}
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>