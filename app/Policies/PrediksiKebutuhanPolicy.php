<?php

namespace App\Policies;

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
        return $user->hasPermissionTo('view_prediksi_kebutuhan');
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
