<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokGudang extends Model
{
    protected $table = 'stok_gudang';

    protected $fillable = [
        'obat_id',
        'jumlah',
        'stok_minimum',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'stok_minimum' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    /**
     * Recalculate stok_gudang.jumlah from batch_stok for a specific obat.
     * SUM all 'tersedia' batches at gudang level (fasilitas_id IS NULL).
     */
    public static function recalculateForObat(int $obatId): void
    {
        $sum = BatchStok::query()
            ->where('obat_id', $obatId)
            ->whereNull('fasilitas_id')
            ->where('status', 'tersedia')
            ->sum('jumlah');

        static::updateOrCreate(
            ['obat_id' => $obatId],
            ['jumlah' => max(0, $sum)],
        );
    }

    /**
     * Recalculate stok_gudang for ALL obat that have batch_stok records.
     */
    public static function recalculateAll(): int
    {
        $obatIds = BatchStok::query()
            ->whereNull('fasilitas_id')
            ->where('status', 'tersedia')
            ->distinct()
            ->pluck('obat_id');

        foreach ($obatIds as $obatId) {
            static::recalculateForObat($obatId);
        }

        return $obatIds->count();
    }
}
