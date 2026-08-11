<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use App\Services\NomorFormatService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenerimaanStok extends Model
{
    use LogsActivity;

    protected $table = 'penerimaan_stok';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $record) {
            if (blank($record->nomor_penerimaan)) {
                $record->nomor_penerimaan = static::generateNomorPenerimaan(
                    $record->fasilitas_id,
                    $record->tanggal_penerimaan?->format('Y-m-d'),
                );
            }
        });
    }

    public static function generateNomorPenerimaan(?int $fasilitasId = null, ?string $date = null): string
    {
        return NomorFormatService::generate('penerimaan_stok', $fasilitasId, $date);
    }

    protected $fillable = [
        'nomor_penerimaan',
        'tipe',
        'supplier_id',
        'nomor_po',
        'nomor_invoice',
        'tanggal_penerimaan',
        'fasilitas_id',
        'user_id',
        'status',
        'catatan',
        'total_biaya',
        'sumber_dana_id',
        'distribusi_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_penerimaan' => 'date',
            'total_biaya' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'penerimaan_stok';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPenerimaanStok::class, 'penerimaan_id');
    }

    public function batchStok(): HasMany
    {
        return $this->hasMany(BatchStok::class, 'penerimaan_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function distribusi(): BelongsTo
    {
        return $this->belongsTo(DistribusiObat::class, 'distribusi_id');
    }
}
