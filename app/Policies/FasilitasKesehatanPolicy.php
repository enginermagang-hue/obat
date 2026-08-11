<?php

namespace App\Policies;

use App\Models\FasilitasKesehatan;
use App\Models\User;

class FasilitasKesehatanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_fasilitas_kesehatan');
    }

    public function view(User $user, FasilitasKesehatan $fasilitasKesehatan): bool
    {
        return $user->hasPermissionTo('view_fasilitas_kesehatan');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_fasilitas_kesehatan');
    }

    public function update(User $user, FasilitasKesehatan $fasilitasKesehatan): bool
    {
        return $user->hasPermissionTo('update_fasilitas_kesehatan');
    }

    public function delete(User $user, FasilitasKesehatan $fasilitasKesehatan): bool
    {
        return $user->hasPermissionTo('delete_fasilitas_kesehatan');
    }
}
