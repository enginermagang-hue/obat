<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelPrediksi extends Model
{
    use LogsActivity;

    protected $table = 'model_prediksi';

    protected $fillable = [
        'fasilitas_id',
        'obat_id',
        'model_data',
        'model_path',
        'akurasi_r2',
        'mae',
        'mape',
        'tanggal_training',
        'data_training_count',
        'fitur_digunakan',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'akurasi_r2' => 'decimal:4',
            'mae' => 'decimal:2',
            'mape' => 'decimal:2',
            'tanggal_training' => 'date',
            'data_training_count' => 'integer',
            'fitur_digunakan' => 'array',
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

    public function prediksiKebutuhan(): HasMany
    {
        return $this->hasMany(PrediksiKebutuhan::class, 'model_id');
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'aktif' => 'success',
            'kadaluarsa' => 'warning',
            'gagal' => 'danger',
            'data_belum_cukup' => 'gray',
            default => 'secondary',
        };
    }
}
