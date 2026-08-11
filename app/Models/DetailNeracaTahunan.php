<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailNeracaTahunan extends Model
{
    protected $table = 'detail_neraca_tahunan';

    protected $fillable = [
        'neraca_id',
        'obat_id',
        'stok_awal',
        'total_masuk',
        'total_keluar',
        'stok_akhir',
        'stok_optimum',
        'permintaan',
        'harga_satuan',
        'nilai_stok',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'stok_awal' => 'integer',
            'total_masuk' => 'integer',
            'total_keluar' => 'integer',
            'stok_akhir' => 'integer',
            'stok_optimum' => 'integer',
            'permintaan' => 'integer',
            'harga_satuan' => 'decimal:2',
            'nilai_stok' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function neracaTahunan(): BelongsTo
    {
        return $this->belongsTo(NeracaTahunan::class, 'neraca_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function sumberDanaDetails(): HasMany
    {
        return $this->hasMany(DetailNeracaSumberDana::class, 'detail_neraca_id');
    }
}
