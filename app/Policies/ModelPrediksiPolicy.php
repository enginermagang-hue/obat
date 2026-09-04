<?php

namespace App\Policies;

use App\Filament\Pages\PrediksiAiPage;
use App\Models\ModelPrediksi;
use App\Models\User;

class ModelPrediksiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_model_prediksi');
    }

    public function view(User $user, ModelPrediksi $modelPrediksi): bool
    {
        if (! $user->hasPermissionTo('view_model_prediksi')) {
            return false;
        }
        if (blank($user->fasilitas_kesehatan_id) || $user->hasRole('super_admin') || $user->hasRole('admin_dinas')) {
            return true;
        }
        // Faskes: only own fasilitas + pustu (if puskesmas)
        $visible = PrediksiAiPage::getVisibleFasilitasIds($user);

        return in_array((int) $modelPrediksi->fasilitas_id, $visible, true);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_model_prediksi');
    }

    public function update(User $user, ModelPrediksi $modelPrediksi): bool
    {
        return $user->hasPermissionTo('update_model_prediksi');
    }

    public function delete(User $user, ModelPrediksi $modelPrediksi): bool
    {
        return $user->hasPermissionTo('delete_model_prediksi');
    }
}
