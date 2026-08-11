<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use App\Services\NomorFormatService;
use Database\Factories\PemakaianObatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PemakaianObat extends Model
{
    /** @use HasFactory<PemakaianObatFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'pemakaian_obat';

    protected $fillable = [
        'nomor_pemakaian',
        'fasilitas_id',
        'tanggal_pemakaian',
        'jenis_pelayanan',
        'nama_pasien',
        'no_rekam_medis',
        'diagnosa_kode',
        'user_id',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pemakaian' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'pemakaian_obat';
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPemakaianObat::class, 'pemakaian_id');
    }

    public function riwayatStok(): HasManyThrough
    {
        return $this->hasManyThrough(
            RiwayatStok::class,
            DetailPemakaianObat::class,
            'pemakaian_id',
            'referensi_id',
            'id',
            'id',
        )->where('riwayat_stok.referensi_type', DetailPemakaianObat::class)
            ->orderByDesc('riwayat_stok.id');
    }

    /**
     * Human-readable label for jenis_pelayanan enum.
     */
    public function getJenisPelayananLabelAttribute(): string
    {
        return match ($this->jenis_pelayanan) {
            'rawat_jalan' => 'Rawat Jalan',
            'rawat_inap' => 'Rawat Inap',
            'uks' => 'UKS',
            'posyandu' => 'Posyandu',
            'pusling' => 'Pusling',
            'gigi' => 'Poli Gigi',
            'laboratorium' => 'Laboratorium',
            'apotek' => 'Apotek',
            'lainnya' => 'Lainnya',
            default => (string) $this->jenis_pelayanan,
        };
    }

    public static function generateNomorPemakaian(string|\DateTimeInterface $tanggal, ?int $fasilitasId = null): string
    {
        $date = $tanggal instanceof \DateTimeInterface ? $tanggal : now()->parse($tanggal);

        return NomorFormatService::generate('pemakaian_obat', $fasilitasId, $date->format('Y-m-d'));
    }
}
