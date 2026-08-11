<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use App\Services\NomorFormatService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpnameStok extends Model
{
    use LogsActivity;

    protected $table = 'opname_stok';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $record) {
            if (blank($record->nomor_opname)) {
                $record->nomor_opname = static::generateNomorOpname($record);
            }
        });
    }

    public static function generateNomorOpname(self $record, ?string $tipe = null): string
    {
        $prefix = match ($tipe) {
            'stok_awal' => 'STK-AWAL',
            'stok_baru' => 'STK-BARU',
            default => 'OPN',
        };

        return NomorFormatService::generate(
            'opname_stok',
            $record->fasilitas_id,
            $record->tanggal_opname?->format('Y-m-d'),
            ['PREFIX' => $prefix],
        );
    }

    protected $fillable = [
        'nomor_opname',
        'tipe',
        'fasilitas_id',
        'tanggal_opname',
        'status',
        'user_id',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_opname' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogName(): string
    {
        return 'opname_stok';
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
        return $this->hasMany(DetailOpnameStok::class, 'opname_id');
    }
}
