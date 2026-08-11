<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailOpnameStok extends Model
{
    protected $table = 'detail_opname_stok';

    protected $fillable = [
        'opname_id',
        'obat_id',
        'batch_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'batch_number',
        'tanggal_expired',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
            'tanggal_expired' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(OpnameStok::class, 'opname_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchStok::class, 'batch_id');
    }
}
