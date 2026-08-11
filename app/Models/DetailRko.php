<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailRko extends Model
{
    protected $table = 'detail_rko';

    protected $fillable = [
        'rko_id',
        'obat_id',
        'pemakaian_tahun_sebelumnya',
        'rata_rata_pemakaian_bulanan',
        'stok_akhir',
        'kebutuhan_tahunan',
        'usulan',
        'harga_perkiraan',
        'total_harga',
        'buffer_stock_persen',
        'buffer_stok_qty',
        'rencana_kebutuhan',
        'total_kebutuhan',
        'ven_kategori',
        'abc_kategori',
        'lplpo_referensi_id',
        'prediksi_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'pemakaian_tahun_sebelumnya' => 'integer',
            'rata_rata_pemakaian_bulanan' => 'integer',
            'stok_akhir' => 'integer',
            'kebutuhan_tahunan' => 'integer',
            'usulan' => 'integer',
            'harga_perkiraan' => 'decimal:2',
            'total_harga' => 'decimal:2',
            'buffer_stock_persen' => 'decimal:2',
            'buffer_stok_qty' => 'integer',
            'rencana_kebutuhan' => 'integer',
            'total_kebutuhan' => 'integer',
            'ven_kategori' => 'string',
            'abc_kategori' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function laporanRko(): BelongsTo
    {
        return $this->belongsTo(LaporanRko::class, 'rko_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function lplpoReferensi(): BelongsTo
    {
        return $this->belongsTo(LaporanLplpo::class, 'lplpo_referensi_id');
    }

    public function prediksi(): BelongsTo
    {
        return $this->belongsTo(PrediksiKebutuhan::class, 'prediksi_id');
    }
}
