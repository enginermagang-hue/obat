<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailDistribusiObat extends Model
{
    protected $table = 'detail_distribusi_obat';

    protected $fillable = [
        'distribusi_id',
        'obat_id',
        'batch_id',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function distribusi(): BelongsTo
    {
        return $this->belongsTo(DistribusiObat::class, 'distribusi_id');
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
