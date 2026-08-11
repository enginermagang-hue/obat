<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SumberDanaPenggunaan extends Model
{
    use LogsActivity;

    protected $table = 'sumber_dana_penggunaan';

    protected $fillable = [
        'sumber_dana_id',
        'rko_id',
        'fasilitas_id',
        'tipe',
        'jumlah_obat',
        'total_biaya',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_obat' => 'integer',
            'total_biaya' => 'decimal:2',
            'tanggal' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function laporanRko(): BelongsTo
    {
        return $this->belongsTo(LaporanRko::class, 'rko_id');
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }
}
