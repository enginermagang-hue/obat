<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenerimaanStok extends Model
{
    protected $table = 'detail_penerimaan_stok';

    protected $fillable = [
        'penerimaan_id',
        'obat_id',
        'batch_number',
        'tanggal_expired',
        'jumlah',
        'harga_satuan',
        'sub_total',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_expired' => 'date',
            'harga_satuan' => 'decimal:2',
            'sub_total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanStok::class, 'penerimaan_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
}
