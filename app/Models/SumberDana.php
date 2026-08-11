<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumberDana extends Model
{
    use LogsActivity;

    protected $table = 'sumber_dana';

    protected $fillable = [
        'kode',
        'nama',
        'tahun',
        'total_anggaran',
        'keterangan',
        'status',
    ];

    public function setStatusAttribute(bool $value): void
    {
        $this->attributes['status'] = $value ? 'aktif' : 'nonaktif';
    }

    public function getStatusAttribute(string $value): bool
    {
        return $value === 'aktif';
    }

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'total_anggaran' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function laporanRko(): HasMany
    {
        return $this->hasMany(LaporanRko::class, 'sumber_dana_id');
    }

    public function penerimaanStok(): HasMany
    {
        return $this->hasMany(PenerimaanStok::class, 'sumber_dana_id');
    }

    public function batchStok(): HasMany
    {
        return $this->hasMany(BatchStok::class, 'sumber_dana_id');
    }

    public function sumberDanaPenggunaans(): HasMany
    {
        return $this->hasMany(SumberDanaPenggunaan::class, 'sumber_dana_id');
    }
}
