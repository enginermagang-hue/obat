<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use App\Services\NomorFormatService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturObat extends Model
{
    use LogsActivity;

    protected $table = 'retur_obat';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $record) {
            if (blank($record->nomor_retur)) {
                $record->nomor_retur = static::generateNomorRetur(
                    $record->fasilitas_pengirim_id,
                    $record->tanggal_retur?->format('Y-m-d'),
                );
            }
        });
    }

    public static function generateNomorRetur(?int $fasilitasId = null, ?string $date = null): string
    {
        return NomorFormatService::generate('retur_obat', $fasilitasId, $date);
    }

    protected $fillable = [
        'nomor_retur',
        'distribusi_id',
        'penerimaan_id',
        'fasilitas_pengirim_id',
        'fasilitas_penerima_id',
        'supplier_id',
        'tipe_retur',
        'alasan',
        'alasan_lainnya',
        'status',
        'tanggal_retur',
        'tanggal_disetujui',
        'tanggal_ditolak',
        'tanggal_dikirim',
        'tanggal_diterima',
        'disetujui_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_retur' => 'date',
            'tanggal_disetujui' => 'date',
            'tanggal_ditolak' => 'date',
            'tanggal_dikirim' => 'date',
            'tanggal_diterima' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'retur_obat';
    }

    public function distribusi(): BelongsTo
    {
        return $this->belongsTo(DistribusiObat::class, 'distribusi_id');
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanStok::class, 'penerimaan_id');
    }

    public function fasilitasPengirim(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_pengirim_id');
    }

    public function fasilitasPenerima(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_penerima_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailReturObat::class, 'retur_id');
    }
}
