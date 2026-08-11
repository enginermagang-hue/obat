<?php

namespace App\Models\Traits;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * Automatically log created / updated / deleted events for a model
 * via ActivityLogService.
 *
 * Usage: add `use LogsActivity;` inside your model class.
 */
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            $user = auth()?->user();

            if ($user !== null) {
                app(ActivityLogService::class)->created($model, $user);
            }
        });

        static::updated(function (Model $model): void {
            $user = auth()?->user();

            if ($user !== null) {
                app(ActivityLogService::class)->updated($model, $user, $model->getChanges());
            }
        });

        static::deleted(function (Model $model): void {
            $user = auth()?->user();

            if ($user !== null) {
                app(ActivityLogService::class)->deleted($model, $user);
            }
        });
    }
}
