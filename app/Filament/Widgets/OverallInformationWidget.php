<?php

namespace App\Filament\Widgets;

use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\Supplier;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OverallInformationWidget extends Widget
{
    protected string $view = 'filament.widgets.overall-information';

    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    public int $totalSuppliers = 0;

    public int $totalFasilitas = 0;

    public int $totalObat = 0;

    public int $totalPenerimaan = 0;

    public int $totalPermintaan = 0;

    public int $penerimaanPercentage = 0;

    public int $permintaanPercentage = 0;

    public int $ring1Percentage = 0;

    public int $ring2Percentage = 0;

    public string $selectedPeriod = 'month';

    public function mount(): void
    {
        $this->loadData();
    }

    public function updatedSelectedPeriod(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $now = Carbon::now();
        $user = Auth::user();
        $fasilitasId = $user?->fasilitas_kesehatan_id;
        $hasFasilitas = filled($fasilitasId);

        $this->totalSuppliers = Supplier::count();
        $this->totalFasilitas = FasilitasKesehatan::count();
        $this->totalObat = Obat::count();

        $penerimaanQuery = PenerimaanStok::query();
        $permintaanQuery = PermintaanObat::query();

        if ($hasFasilitas) {
            $penerimaanQuery->where('fasilitas_id', $fasilitasId);
            $permintaanQuery->where('fasilitas_pengirim_id', $fasilitasId);
        }

        match ($this->selectedPeriod) {
            'week' => [
                $penerimaanQuery->where('tanggal_penerimaan', '>=', $now->copy()->startOfWeek()),
                $permintaanQuery->where('created_at', '>=', $now->copy()->startOfWeek()),
            ],
            'month' => [
                $penerimaanQuery->where('tanggal_penerimaan', '>=', $now->copy()->startOfMonth()),
                $permintaanQuery->where('created_at', '>=', $now->copy()->startOfMonth()),
            ],
            'year' => [
                $penerimaanQuery->where('tanggal_penerimaan', '>=', $now->copy()->startOfYear()),
                $permintaanQuery->where('created_at', '>=', $now->copy()->startOfYear()),
            ],
        };

        $this->totalPenerimaan = $penerimaanQuery->count();
        $this->totalPermintaan = $permintaanQuery->count();
        $total = $this->totalPenerimaan + $this->totalPermintaan;

        $this->penerimaanPercentage = 0;
        $this->permintaanPercentage = 0;

        if ($total > 0) {
            $this->penerimaanPercentage = (int) round(($this->totalPenerimaan / $total) * 100);
            $this->permintaanPercentage = 100 - $this->penerimaanPercentage;
        }

        $this->ring1Percentage = min($this->penerimaanPercentage * 3, 100);
        $this->ring2Percentage = min($this->permintaanPercentage * 3, 100);
    }
}
