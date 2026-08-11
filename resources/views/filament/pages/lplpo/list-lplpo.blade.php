<x-filament-panels::page>
    @if ($isPuskesmasUser)
        <div class="ruang-obat-tab-container">
            <button wire:click="filterByTab('sendiri')" type="button" class="ruang-obat-tab-button
                                {{ $activeTab === 'sendiri' ? 'ruang-obat-tab-active' : 'ruang-obat-tab-inactive' }}">
                LPLPO Sendiri
                <span
                    class="ruang-obat-tab-button-badge
                                {{ $activeTab === 'sendiri' ? 'ruang-obat-tab-badge-active' : 'ruang-obat-tab-badge-inactive' }}">
                    {{ $lplpoSendiriCount }}
                </span>
            </button>
            <button wire:click="filterByTab('pustu_bawahan')" type="button" class="ruang-obat-tab-button
                                {{ $activeTab === 'pustu_bawahan' ? 'ruang-obat-tab-active' : 'ruang-obat-tab-inactive' }}">
                LPLPO Pustu Bawahan
                <span
                    class="ruang-obat-tab-button-badge
                                {{ $activeTab === 'pustu_bawahan' ? 'ruang-obat-tab-badge-active' : 'ruang-obat-tab-badge-inactive' }}">
                    {{ $lplpoPustuBawahanCount }}
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
