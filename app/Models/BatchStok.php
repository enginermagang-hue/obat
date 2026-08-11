<?php

namespace App\Models;

use Database\Factories\BatchStokFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchStok extends Model
{
    /** @use HasFactory<BatchStokFactory> */
    use HasFactory;

    protected $table = 'batch_stok';

    protected $fillable = [
        'penerimaan_id',
        'fasilitas_id',
        'obat_id',
        'batch_number',
        'tanggal_expired',
        'jumlah',
        'status',
        'tanggal_masuk',
        'harga_beli',
        'sumber_dana_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_expired' => 'date',
            'tanggal_masuk' => 'date',
            'harga_beli' => 'decimal:2',
            'jumlah' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    //  Recalculation helpers — sync aggregate tables from batch_stok
    // -----------------------------------------------------------------------

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanStok::class, 'penerimaan_id');
    }

    /**
     * Recalculate stok_gudang.jumlah for the given obat from batch_stok.
     */
    public static function recalculateGudang(int $obatId): void
    {
        StokGudang::recalculateForObat($obatId);
    }

    /**
     * Recalculate stok_faskes.jumlah for the given obat + fasilitas from batch_stok.
     */
    public static function recalculateFaskes(int $fasilitasId, int $obatId): void
    {
        StokFaskes::recalculateForObat($fasilitasId, $obatId);
    }

    /**
     * Recalculate both aggregate tables for the given batch record's context.
     * Call this after any mutation on a BatchStok row.
     */
    public static function recalculateFromBatch(self $batch): void
    {
        if ($batch->fasilitas_id === null) {
            static::recalculateGudang($batch->obat_id);
        } else {
            static::recalculateFaskes($batch->fasilitas_id, $batch->obat_id);
        }
    }
}
