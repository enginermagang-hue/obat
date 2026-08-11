<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use App\Services\NomorFormatService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanObat extends Model
{
    use LogsActivity;

    protected $table = 'permintaan_obat';

    protected $fillable = [
        'nomor_permintaan',
        'fasilitas_pengirim_id',
        'fasilitas_tujuan_id',
        'tipe_permintaan',
        'lplpo_id',
        'status',
        'tanggal_permintaan',
        'tanggal_disetujui',
        'tanggal_ditolak',
        'tanggal_dikirim',
        'tanggal_diterima',
        'disetujui_oleh',
        'catatan',
        'alasan_penolakan',
        'surat_permintaan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_permintaan' => 'date',
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
        return 'permintaan_obat';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $record) {
            if (blank($record->nomor_permintaan)) {
                $record->nomor_permintaan = static::generateNomorPermintaan(
                    $record->fasilitas_pengirim_id,
                    $record->tanggal_permintaan?->format('Y-m-d'),
                );
            }
        });
    }

    public static function generateNomorPermintaan(?int $fasilitasId = null, ?string $date = null): string
    {
        return NomorFormatService::generate('permintaan_obat', $fasilitasId, $date);
    }

    public function fasilitasPengirim(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_pengirim_id');
    }

    public function fasilitasTujuan(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_tujuan_id');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPermintaanObat::class, 'permintaan_id');
    }

    public function distribusi(): HasMany
    {
        return $this->hasMany(DistribusiObat::class, 'permintaan_id');
    }
}
