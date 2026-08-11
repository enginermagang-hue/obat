<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AktivitasTerakhirWidget extends Widget
{
    protected string $view = 'filament.widgets.aktivitas-terakhir';

    protected int|string|array $columnSpan = 'full';

    /** @var Collection */
    public $activities;

    public function mount(): void
    {
        $user = Auth::user();

        $isGlobalAdmin = $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('admin_dinas') ||
            $user->hasRole('admin_gudang')
        );

        $query = ActivityLog::with('causer');

        if (! $isGlobalAdmin) {
            $fasilitasId = $user?->fasilitas_kesehatan_id;

            if (filled($fasilitasId)) {
                $userIdsInFaskes = User::where('fasilitas_kesehatan_id', $fasilitasId)
                    ->pluck('id');

                $query->whereIn('causer_id', $userIdsInFaskes)
                    ->where('causer_type', User::class);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $this->activities = $query->latest()->take(8)->get();
    }
}
