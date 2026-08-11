<?php

namespace App\Filament\Widgets;

use App\Models\BatchStok;
use App\Models\PermintaanObat;
use App\Models\StokFaskes;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class RingkasanWidget extends Widget
{
    protected string $view = 'filament.widgets.ringkasan';

    protected int|string|array $columnSpan = 'full';

    public string $activeTab = 'permintaan';

    /** @var Collection */
    public $permintaans;

    /** @var Collection */
    public $items;

    /** @var Collection */
    public $batches;

    public function mount(): void
    {
        $user = Auth::user();
        $fasilitasId = $user?->fasilitas_kesehatan_id;

        $this->permintaans = PermintaanObat::query()
            ->with(['fasilitasPengirim', 'fasilitasTujuan'])
            ->latest('tanggal_permintaan')
            ->take(5)
            ->get();

        $this->items = StokFaskes::query()
            ->whereColumn('jumlah', '<', 'stok_minimum')
            ->where('stok_minimum', '>', 0)
            ->when(filled($fasilitasId), fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->with('obat')
            ->orderBy('jumlah')
            ->take(5)
            ->get();

        $this->batches = BatchStok::query()
            ->where('status', 'tersedia')
            ->where('tanggal_expired', '<=', now()->addDays(30))
            ->where('tanggal_expired', '>=', now())
            ->when(filled($fasilitasId), fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->with('obat')
            ->orderBy('tanggal_expired')
            ->take(5)
            ->get();

        $tabs = $this->getVisibleTabs();
        if (! in_array($this->activeTab, $tabs)) {
            $this->activeTab = $tabs[0] ?? 'permintaan';
        }
    }

    public function getVisibleTabs(): array
    {
        $user = Auth::user();

        if ($user?->hasRole('pustu')) {
            return ['permintaan'];
        }

        return ['permintaan', 'stok', 'batch'];
    }
}
