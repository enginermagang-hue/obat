<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'avatar_type',
        'avatar_path',
        'avatar_dicebear_style',
        'posisi_navbar',
        'sidebar_collapsed',
        'bahasa',
        'items_per_halaman',
        'notifikasi_email',
        'notifikasi_browser',
    ];

    protected function casts(): array
    {
        return [
            'sidebar_collapsed' => 'boolean',
            'notifikasi_email' => 'boolean',
            'notifikasi_browser' => 'boolean',
            'items_per_halaman' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultsForUser(?Authenticatable $user): array
    {
        if (! $user) {
            return static::defaults();
        }

        $prefs = static::where('user_id', $user->id)->first();

        return $prefs
            ? $prefs->toArray()
            : array_merge(static::defaults(), ['user_id' => $user->id]);
    }

    public static function defaults(): array
    {
        return [
            'avatar_type' => 'initials',
            'avatar_path' => null,
            'avatar_dicebear_style' => 'avataaars',
            'posisi_navbar' => 'sidebar',
            'sidebar_collapsed' => true,
            'bahasa' => 'id',
            'items_per_halaman' => 10,
            'notifikasi_email' => true,
            'notifikasi_browser' => true,
        ];
    }

    public static function dicebearStyles(): array
    {
        return [
            'avataaars' => 'Avataaars',
            'lorelei' => 'Lorelei',
            'micah' => 'Micah',
            'fun-emoji' => 'Fun Emoji',
            'pixel-art' => 'Pixel Art',
            'identicon' => 'Identicon',
            'thumbs' => 'Thumbs',
        ];
    }
}
