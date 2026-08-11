<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanLplpo extends Model
{
    use LogsActivity;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'laporan_lplpo';

    protected $fillable = [
        'nomor_laporan',
        'fasilitas_id',
        'periode_bulan',
        'periode_tahun',
        'status',
        'tanggal_pembuatan',
        'dibuat_oleh',
        'parent_lplpo_id',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'tanggal_pembuatan' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'laporan_lplpo';
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function parentLplpo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_lplpo_id');
    }

    public function revisiLplpo(): HasMany
    {
        return $this->hasMany(self::class, 'parent_lplpo_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailLplpo::class, 'lplpo_id');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('parent_lplpo_id');
    }
}
