<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistribusiObat extends Model
{
    use LogsActivity;

    protected $table = 'distribusi_obat';

    protected $fillable = [
        'nomor_surat_jalan',
        'permintaan_id',
        'tipe_distribusi',
        'fasilitas_pengirim_id',
        'fasilitas_penerima_id',
        'status',
        'tanggal_kirim',
        'tanggal_terima',
        'tanggal_ditolak',
        'pengirim_id',
        'penerima_id',
        'penerimaan_stok_id',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kirim' => 'date',
            'tanggal_terima' => 'date',
            'tanggal_ditolak' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'distribusi_obat';
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanObat::class, 'permintaan_id');
    }

    public function fasilitasPengirim(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_pengirim_id');
    }

    public function fasilitasPenerima(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_penerima_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailDistribusiObat::class, 'distribusi_id');
    }

    public function returs(): HasMany
    {
        return $this->hasMany(ReturObat::class, 'distribusi_id');
    }

    public function penerimaanStok(): BelongsTo
    {
        return $this->belongsTo(PenerimaanStok::class, 'penerimaan_stok_id');
    }
}
