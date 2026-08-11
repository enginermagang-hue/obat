<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * App\Models\ActivityLog
 *
 * Custom model for activity log records. Extends Spatie's model so Filament
 * can auto-discover policies and provide type-safe relations.
 *
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $event
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property mixed $attribute_changes
 * @property mixed $properties
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ActivityLog extends SpatieActivity
{
    //
}
