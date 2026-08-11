<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetupConfiguration extends Model
{
    protected $fillable = [
        'is_setup_completed',
        'organization_name',
        'organization_code',
        'organization_description',
        'primary_facility_name',
        'primary_facility_code',
        'admin_email',
        'admin_name',
        'superadmin_email',
        'superadmin_name',
        'pdf_header',
        'pdf_footer',
        'document_number_format',
        'document_number_sequence',
        'setup_attempt_count',
        'last_setup_attempt_at',
        'setup_completed_at',
    ];

    protected $casts = [
        'is_setup_completed' => 'boolean',
        'setup_completed_at' => 'datetime',
        'last_setup_attempt_at' => 'datetime',
    ];

    public static function isSetupCompleted(): bool
    {
        return self::first()?->is_setup_completed ?? false;
    }

    public static function getConfig(): self
    {
        return self::firstOrCreate([]);
    }

    public function markSetupCompleted(): void
    {
        $this->update([
            'is_setup_completed' => true,
            'setup_completed_at' => now(),
        ]);
    }

    public function isSetupLocked(): bool
    {
        return $this->setup_attempt_count >= 5;
    }

    public function incrementAttempt(): void
    {
        $this->update([
            'setup_attempt_count' => $this->setup_attempt_count + 1,
            'last_setup_attempt_at' => now(),
        ]);
    }

    public function resetAttempt(): void
    {
        $this->update([
            'setup_attempt_count' => 0,
            'last_setup_attempt_at' => null,
        ]);
    }
}
