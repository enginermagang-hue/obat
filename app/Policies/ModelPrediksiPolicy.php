<?php

namespace App\Policies;

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
        return $user->hasPermissionTo('view_model_prediksi');
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
