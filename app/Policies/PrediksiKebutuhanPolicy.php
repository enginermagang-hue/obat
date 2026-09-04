<?php

namespace App\Policies;

use App\Filament\Pages\PrediksiAiPage;
use App\Models\PrediksiKebutuhan;
use App\Models\User;

class PrediksiKebutuhanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_prediksi_kebutuhan');
    }

    public function view(User $user, PrediksiKebutuhan $prediksiKebutuhan): bool
    {
        if (! $user->hasPermissionTo('view_prediksi_kebutuhan')) {
            return false;
        }
        if (blank($user->fasilitas_kesehatan_id) || $user->hasRole('super_admin') || $user->hasRole('admin_dinas')) {
            return true;
        }
        $visible = PrediksiAiPage::getVisibleFasilitasIds($user);

        return in_array((int) $prediksiKebutuhan->fasilitas_id, $visible, true);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_prediksi_kebutuhan');
    }

    public function update(User $user, PrediksiKebutuhan $prediksiKebutuhan): bool
    {
        return $user->hasPermissionTo('update_prediksi_kebutuhan');
    }

    public function delete(User $user, PrediksiKebutuhan $prediksiKebutuhan): bool
    {
        return $user->hasPermissionTo('delete_prediksi_kebutuhan');
    }
}
