<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Database\Factories\DetailPemakaianObatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPemakaianObat extends Model
{
    /** @use HasFactory<DetailPemakaianObatFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'detail_pemakaian_obat';

    protected $fillable = [
        'pemakaian_id',
        'obat_id',
        'batch_id',
        'jumlah',
        'dosis',
        'satuan_dosis',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'pemakaian_obat';
    }

    public function pemakaian(): BelongsTo
    {
        return $this->belongsTo(PemakaianObat::class, 'pemakaian_id');
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
