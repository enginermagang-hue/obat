<?php

namespace App\Filament\Widgets;

use App\Models\PemakaianObat;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PemakaianTerbaruWidget extends Widget
{
    protected string $view = 'filament.widgets.pemakaian-terbaru';

    protected int|string|array $columnSpan = 'full';

    public Collection $pemakaians;

    public function mount(): void
    {
        $user = Auth::user();
        $fasilitasId = $user?->fasilitas_kesehatan_id;

        $this->pemakaians = PemakaianObat::query()
            ->with(['fasilitas', 'user'])
            ->when($fasilitasId, fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->latest('tanggal_pemakaian')
            ->take(5)
            ->get();
    }
}
