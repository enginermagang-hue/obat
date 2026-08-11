<?php

namespace App\Models;

use App\Enums\MetodeStok;
use App\Models\Traits\LogsActivity;
use Database\Factories\ObatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Obat extends Model
{
    /** @use HasFactory<ObatFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'obat';

    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'nama_generik',
        'kategori',
        'satuan',
        'kekuatan',
        'bentuk_sediaan',
        'produsen',
        'kemasan',
        'harga_satuan',
        'status',
        'ven_kategori',
        'metode_stok',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'ven_kategori' => 'string',
            'metode_stok' => MetodeStok::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function batchStok(): HasMany
    {
        return $this->hasMany(BatchStok::class, 'obat_id');
    }

    public function modelPrediksi(): HasMany
    {
        return $this->hasMany(ModelPrediksi::class, 'obat_id');
    }

    public function prediksiKebutuhan(): HasMany
    {
        return $this->hasMany(PrediksiKebutuhan::class, 'obat_id');
    }

    public function stokGudang(): HasOne
    {
        return $this->hasOne(StokGudang::class, 'obat_id');
    }

    public function stokFaskes(): HasMany
    {
        return $this->hasMany(StokFaskes::class, 'obat_id');
    }
}
