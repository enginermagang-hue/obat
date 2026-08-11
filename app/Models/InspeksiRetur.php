<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspeksiRetur extends Model
{
    protected $table = 'inspeksi_retur';

    protected $fillable = [
        'retur_id',
        'detail_retur_id',
        'batch_id',
        'hasil_inspeksi',
        'tindakan',
        'catatan_inspeksi',
        'inspected_by',
        'tanggal_inspeksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_inspeksi' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function retur(): BelongsTo
    {
        return $this->belongsTo(ReturObat::class, 'retur_id');
    }

    public function detailRetur(): BelongsTo
    {
        return $this->belongsTo(DetailReturObat::class, 'detail_retur_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchStok::class, 'batch_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
