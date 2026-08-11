<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailLplpo extends Model
{
    protected $table = 'detail_lplpo';

    protected $fillable = [
        'lplpo_id',
        'obat_id',
        'stok_awal',
        'jumlah_masuk',
        'jumlah_keluar',
        'sisa_stok',
        'stok_optimum',
        'permintaan_selanjutnya',
        'sudah_diminta',
        'permintaan_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'stok_awal' => 'integer',
            'jumlah_masuk' => 'integer',
            'jumlah_keluar' => 'integer',
            'sisa_stok' => 'integer',
            'stok_optimum' => 'integer',
            'permintaan_selanjutnya' => 'integer',
            'sudah_diminta' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function laporanLplpo(): BelongsTo
    {
        return $this->belongsTo(LaporanLplpo::class, 'lplpo_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanObat::class, 'permintaan_id');
    }
}
