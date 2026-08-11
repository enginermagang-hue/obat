<?php

namespace App\Policies;

use App\Models\SumberDana;
use App\Models\User;

class SumberDanaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_sumber_dana');
    }

    public function view(User $user, SumberDana $sumberDana): bool
    {
        return $user->hasPermissionTo('view_sumber_dana');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_sumber_dana');
    }

    public function update(User $user, SumberDana $sumberDana): bool
    {
        return $user->hasPermissionTo('update_sumber_dana');
    }

    public function delete(User $user, SumberDana $sumberDana): bool
    {
        return $user->hasPermissionTo('delete_sumber_dana');
    }
}
