<?php

namespace App\Policies;

use App\Models\Obat;
use App\Models\User;

class ObatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_obat');
    }

    public function view(User $user, Obat $obat): bool
    {
        return $user->hasPermissionTo('view_obat');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_obat');
    }

    public function update(User $user, Obat $obat): bool
    {
        return $user->hasPermissionTo('update_obat');
    }

    public function delete(User $user, Obat $obat): bool
    {
        return $user->hasPermissionTo('delete_obat');
    }
}
