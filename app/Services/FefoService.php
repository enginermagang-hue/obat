<?php

namespace App\Services;

use App\Models\BatchStok;
use Illuminate\Database\Eloquent\Collection;

class FefoService
{
    /**
     * Get all available batches for an obat + fasilitas, sorted by the specified method
     * (fefo: earliest expired first, fifo: earliest received first, lifo: latest received first).
     *
     * @return Collection<int, BatchStok>
     */
    public function getAvailableBatches(int $obatId, ?int $fasilitasId, string $metode = 'fefo'): Collection
    {
        $query = BatchStok::query()
            ->where('obat_id', $obatId)
            ->where('status', 'tersedia')
            ->where('jumlah', '>', 0)
            ->when(
                filled($fasilitasId),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
            );

        if ($metode === 'fifo') {
            $query->orderBy('tanggal_masuk')->orderBy('id');
        } elseif ($metode === 'lifo') {
            $query->orderByDesc('tanggal_masuk')->orderByDesc('id');
        } else {
            // fefo (default)
            $query->orderBy('tanggal_expired')->orderBy('id');
        }

        return $query->get();
    }

    /**
     * Get the best single batch ID for auto-fill dropdown, based on metode.
     */
    public function getBestBatchId(int $obatId, ?int $fasilitasId, string $metode = 'fefo'): ?int
    {
        return $this->getAvailableBatches($obatId, $fasilitasId, $metode)
            ->first()
            ?->id;
    }

    /**
     * Allocate a quantity across sorted batches (based on metode).
     *
     * Takes from batches in order until the quantity is fulfilled.
     * Returns an array of ['batch_id' => int, 'jumlah' => int] entries, one per batch.
     *
     * If total stock is insufficient, returns whatever is available
     * (no exception thrown).
     *
     * @return array<int, array{batch_id: int, jumlah: int}>
     */
    public function allocate(int $obatId, int $jumlah, ?int $fasilitasId, string $metode = 'fefo'): array
    {
        if ($jumlah <= 0) {
            return [];
        }

        $batches = $this->getAvailableBatches($obatId, $fasilitasId, $metode);

        if ($batches->isEmpty()) {
            return [];
        }

        $result = [];
        $remaining = $jumlah;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $ambil = min($batch->jumlah, $remaining);

            $result[] = [
                'batch_id' => $batch->id,
                'jumlah' => $ambil,
            ];

            $remaining -= $ambil;
        }

        return $result;
    }

    /**
     * Check if total available stock is sufficient for a quantity.
     */
    public function hasSufficientStock(int $obatId, int $jumlah, ?int $fasilitasId, string $metode = 'fefo'): bool
    {
        return $this->getAvailableBatches($obatId, $fasilitasId, $metode)
            ->sum('jumlah') >= $jumlah;
    }
}
