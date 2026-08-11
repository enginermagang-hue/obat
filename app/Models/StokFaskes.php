<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokFaskes extends Model
{
    protected $table = 'stok_faskes';

    protected $fillable = [
        'fasilitas_id',
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

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    /**
     * Recalculate stok_faskes.jumlah from batch_stok for a specific obat at a facility.
     * SUM all 'tersedia' batches at the facility level.
     */
    public static function recalculateForObat(int $fasilitasId, int $obatId): void
    {
        $sum = BatchStok::query()
            ->where('obat_id', $obatId)
            ->where('fasilitas_id', $fasilitasId)
            ->where('status', 'tersedia')
            ->sum('jumlah');

        static::updateOrCreate(
            ['fasilitas_id' => $fasilitasId, 'obat_id' => $obatId],
            ['jumlah' => max(0, $sum)],
        );
    }

    /**
     * Recalculate stok_faskes for ALL facility/obat combinations.
     */
    public static function recalculateAll(): int
    {
        $combos = BatchStok::query()
            ->whereNotNull('fasilitas_id')
            ->where('status', 'tersedia')
            ->selectRaw('fasilitas_id, obat_id')
            ->distinct()
            ->get();

        foreach ($combos as $combo) {
            static::recalculateForObat($combo->fasilitas_id, $combo->obat_id);
        }

        return $combos->count();
    }
}
