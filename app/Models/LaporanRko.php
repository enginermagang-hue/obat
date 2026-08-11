<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanRko extends Model
{
    use LogsActivity;

    protected $table = 'laporan_rko';

    protected $fillable = [
        'nomor_rko',
        'fasilitas_id',
        'periode_tahun',
        'status',
        'tanggal_pembuatan',
        'tanggal_pengajuan',
        'tanggal_disetujui',
        'total_anggaran',
        'dibuat_oleh',
        'disetujui_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'periode_tahun' => 'integer',
            'total_anggaran' => 'decimal:2',
            'tanggal_pembuatan' => 'date',
            'tanggal_pengajuan' => 'date',
            'tanggal_disetujui' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'laporan_rko';
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailRko::class, 'rko_id');
    }
}
