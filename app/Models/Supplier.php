<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'suppliers';

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'email',
        'npwp',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function penerimaanStok(): HasMany
    {
        return $this->hasMany(PenerimaanStok::class, 'supplier_id');
    }

    public function getStatusAttribute(): bool
    {
        $raw = $this->attributes['status'] ?? null;

        return in_array($raw, [true, 1, '1', 'aktif'], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ? 'Aktif' : 'Nonaktif';
    }

    public function setStatusAttribute(mixed $value): void
    {
        $this->attributes['status'] = in_array($value, [true, 1, '1', 'aktif'], true) ? 'aktif' : 'nonaktif';
    }
}
