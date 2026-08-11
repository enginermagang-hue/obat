<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPermintaanObat extends Model
{
    protected $table = 'detail_permintaan_obat';

    protected $fillable = [
        'permintaan_id',
        'obat_id',
        'jumlah_diminta',
        'jumlah_disetujui',
        'jumlah_dikirim',
        'jumlah_diterima',
        'catatan',
    ];

    protected $appends = [
        'nama_obat',
        'info_satuan',
        'info_kategori',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanObat::class, 'permintaan_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function getNamaObatAttribute(): ?string
    {
        return $this->obat?->nama_obat;
    }

    public function getInfoSatuanAttribute(): ?string
    {
        return $this->obat?->satuan;
    }

    public function getInfoKategoriAttribute(): ?string
    {
        return $this->obat?->kategori;
    }

    public function getJumlahAttribute(): int
    {
        return $this->jumlah_diminta ?? 0;
    }
}
