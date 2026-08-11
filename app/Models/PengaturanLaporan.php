<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanLaporan extends Model
{
    protected $table = 'pengaturan_laporan';

    protected $fillable = [
        'fasilitas_id',
        'grup',
        'key',
        'value',
    ];

    public function fasilitas()
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'fasilitas_id');
    }

    /**
     * Ambil nilai setting dari grup tertentu.
     * Prioritas: setting milik faskes, fallback ke global (fasilitas_id = null).
     */
    public static function get(string $grup, string $key, ?int $fasilitasId = null): ?string
    {
        if ($fasilitasId) {
            $value = static::where('fasilitas_id', $fasilitasId)
                ->where('grup', $grup)
                ->where('key', $key)
                ->value('value');

            if ($value !== null) {
                return $value;
            }
        }

        return static::whereNull('fasilitas_id')
            ->where('grup', $grup)
            ->where('key', $key)
            ->value('value');
    }

    /**
     * Ambil semua setting dalam grup, merge global + faskes override.
     */
    public static function getAllForFaskes(string $grup, ?int $fasilitasId = null): array
    {
        $query = static::where('grup', $grup)
            ->where(function ($q) use ($fasilitasId) {
                $q->whereNull('fasilitas_id');
                if ($fasilitasId) {
                    $q->orWhere('fasilitas_id', $fasilitasId);
                }
            })
            ->get();

        $global = $query->whereNull('fasilitas_id')->pluck('value', 'key');
        $faskes = $query->where('fasilitas_id', $fasilitasId)->pluck('value', 'key');

        return $faskes->merge($global)->all();
    }
}
