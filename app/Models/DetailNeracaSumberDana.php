<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailNeracaSumberDana extends Model
{
    protected $table = 'detail_neraca_sumber_dana';

    protected $fillable = [
        'detail_neraca_id',
        'sumber_dana_id',
        'stok_awal_jumlah',
        'stok_awal_nilai',
        'masuk_jumlah',
        'masuk_nilai',
        'keluar_jumlah',
        'keluar_nilai',
        'stok_akhir_jumlah',
        'stok_akhir_nilai',
    ];

    protected function casts(): array
    {
        return [
            'stok_awal_jumlah' => 'integer',
            'stok_awal_nilai' => 'decimal:2',
            'masuk_jumlah' => 'integer',
            'masuk_nilai' => 'decimal:2',
            'keluar_jumlah' => 'integer',
            'keluar_nilai' => 'decimal:2',
            'stok_akhir_jumlah' => 'integer',
            'stok_akhir_nilai' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function detailNeraca(): BelongsTo
    {
        return $this->belongsTo(DetailNeracaTahunan::class, 'detail_neraca_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }
}
