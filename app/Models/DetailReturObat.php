<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetailReturObat extends Model
{
    protected $table = 'detail_retur_obat';

    protected $fillable = [
        'retur_id',
        'obat_id',
        'batch_id',
        'jumlah_retur',
        'bukti_foto',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function retur(): BelongsTo
    {
        return $this->belongsTo(ReturObat::class, 'retur_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchStok::class, 'batch_id');
    }

    public function inspeksi(): HasOne
    {
        return $this->hasOne(InspeksiRetur::class, 'detail_retur_id');
    }
}
