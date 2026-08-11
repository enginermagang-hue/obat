<?php

namespace App\Filament\Widgets;

use App\Models\BatchStok;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\StokFaskes;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class InventoryStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.inventory-stats';

    protected int|string|array $columnSpan = 'full';

    /** @var array<int, array{label: string, value: string, icon: string, color: string, accentColor: string, change: string, changeColor: string, description: string}> */
    public array $stats = [];

    public function mount(): void
    {
        $this->stats = $this->getStats();
    }

    /** @return array<int, array{label: string, value: string, icon: string, color: string, accentColor: string, change: string, changeColor: string, description: string}> */
    protected function getStats(): array
    {
        $now = Carbon::now();
        $user = Auth::user();

        $isGlobalAdmin = $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('admin_dinas') ||
            $user->hasRole('admin_gudang')
        );

        $fasilitasId = $user?->fasilitas_kesehatan_id;
        $hasFasilitas = filled($fasilitasId);

        $totalObat = Obat::count();

        $totalBatch = BatchStok::where('status', 'tersedia')
            ->when($hasFasilitas, fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->sum('jumlah');

        // PermintaanObat — replika logika PermintaanObatResource::getEloquentQuery()
        $permintaanQuery = PermintaanObat::query();

        if ($isGlobalAdmin) {
            $permintaanQuery->where('tipe_permintaan', 'puskesmas_ke_dinas');
        } elseif ($hasFasilitas) {
            $userFasilitas = $user?->fasilitasKesehatan;

            if ($userFasilitas && $userFasilitas->tipe === 'puskesmas') {
                $pustuIds = $userFasilitas->pustu()->pluck('fasilitas_kesehatan.id');
                $permintaanQuery->where('tipe_permintaan', 'pustu_ke_puskesmas')
                    ->whereIn('fasilitas_pengirim_id', $pustuIds);
            } else {
                $permintaanQuery->where('tipe_permintaan', 'pustu_ke_puskesmas')
                    ->where('fasilitas_pengirim_id', $fasilitasId);
            }
        } else {
            $permintaanQuery->whereRaw('1 = 0');
        }

        $permintaanPending = (clone $permintaanQuery)->where('status', 'menunggu_persetujuan')->count();
        $permintaanBulanIni = (clone $permintaanQuery)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        // PenerimaanStok — replika logika PenerimaanStokResource
        $penerimaanQuery = PenerimaanStok::query();

        if ($isGlobalAdmin) {
            // semua data
        } elseif ($hasFasilitas) {
            $penerimaanQuery->where('fasilitas_id', $fasilitasId);
        } else {
            $penerimaanQuery->whereRaw('1 = 0');
        }

        $penerimaanBulanIni = (clone $penerimaanQuery)
            ->whereMonth('tanggal_penerimaan', $now->month)
            ->whereYear('tanggal_penerimaan', $now->year)
            ->count();
        $totalBiayaBulanIni = (clone $penerimaanQuery)
            ->whereMonth('tanggal_penerimaan', $now->month)
            ->whereYear('tanggal_penerimaan', $now->year)
            ->sum('total_biaya');

        // StokFaskes — replika logika StokFaskesResource
        $stokQuery = StokFaskes::query();

        if ($hasFasilitas) {
            $stokQuery->where('fasilitas_id', $fasilitasId);
        }

        $stokMenipis = (clone $stokQuery)->whereColumn('jumlah', '<', 'stok_minimum')->count();
        $stokKritis = (clone $stokQuery)->where('jumlah', 0)->count();

        return [
            [
                'label' => 'Total Obat',
                'value' => number_format($totalObat),
                'icon' => 'heroicon-o-cube',
                'color' => 'primary',
                'accentColor' => 'amber',
                'change' => $totalBatch > 0 ? number_format($totalBatch).' batch' : 'Belum ada batch',
                'changeColor' => 'text-primary-600 dark:text-primary-400',
                'description' => 'stok tersedia',
            ],
            [
                'label' => 'Permintaan Pending',
                'value' => number_format($permintaanPending),
                'icon' => 'heroicon-o-arrow-path',
                'color' => 'warning',
                'accentColor' => 'amber',
                'change' => $permintaanBulanIni > 0 ? '+'.number_format($permintaanBulanIni).' bulan ini' : '0 bulan ini',
                'changeColor' => $permintaanPending > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400',
                'description' => 'menunggu persetujuan',
            ],
            [
                'label' => 'Penerimaan Stok',
                'value' => number_format($penerimaanBulanIni),
                'icon' => 'heroicon-o-arrow-down-tray',
                'color' => 'success',
                'accentColor' => 'emerald',
                'change' => $totalBiayaBulanIni > 0 ? 'Rp '.number_format($totalBiayaBulanIni, 0, ',', '.') : 'Rp 0',
                'changeColor' => 'text-emerald-600 dark:text-emerald-400',
                'description' => $now->translatedFormat('F').' '.$now->year,
            ],
            [
                'label' => 'Stok Menipis',
                'value' => number_format($stokMenipis),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'accentColor' => 'red',
                'change' => $stokKritis > 0 ? number_format($stokKritis).' kritis' : 'Aman',
                'changeColor' => $stokMenipis > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400',
                'description' => 'di bawah minimum',
            ],
        ];
    }
}
