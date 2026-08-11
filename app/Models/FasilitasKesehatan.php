<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Database\Factories\FasilitasKesehatanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FasilitasKesehatan extends Model
{
    /** @use HasFactory<FasilitasKesehatanFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'fasilitas_kesehatan';

    protected $fillable = [
        'kode_faskes',
        'nama',
        'tipe',
        'puskesmas_induk_id',
        'alamat',
        'pic',
        'kontak_pic',
        'telepon',
        'kepala_faskes',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function puskesmasInduk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'puskesmas_induk_id');
    }

    public function pustu(): HasMany
    {
        return $this->hasMany(self::class, 'puskesmas_induk_id');
    }

    public function modelPrediksi(): HasMany
    {
        return $this->hasMany(ModelPrediksi::class, 'fasilitas_id');
    }

    public function prediksiKebutuhan(): HasMany
    {
        return $this->hasMany(PrediksiKebutuhan::class, 'fasilitas_id');
    }

    public function stokFaskes(): HasMany
    {
        return $this->hasMany(StokFaskes::class, 'fasilitas_id');
    }
}
