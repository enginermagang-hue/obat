<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrediksiKebutuhan extends Model
{
    use LogsActivity;

    protected $table = 'prediksi_kebutuhan';

    protected $fillable = [
        'fasilitas_id',
        'obat_id',
        'model_id',
        'periode_bulan',
        'periode_tahun',
        'jumlah_prediksi',
        'confidence_lower',
        'confidence_upper',
        'metode',
        'dibuat_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'jumlah_prediksi' => 'integer',
            'confidence_lower' => 'integer',
            'confidence_upper' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'prediksi_kebutuhan';
    }

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(ModelPrediksi::class, 'model_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
