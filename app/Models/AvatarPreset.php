<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvatarPreset extends Model
{
    protected $fillable = [
        'nama',
        'file_path',
        'kategori',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
