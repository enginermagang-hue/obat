<?php

namespace App\Policies;

use App\Models\OpnameStok;
use App\Models\User;

class OpnameStokPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_opname_stok');
    }

    public function view(User $user, OpnameStok $opnameStok): bool
    {
        return $user->hasPermissionTo('view_opname_stok');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_opname_stok');
    }

    public function update(User $user, OpnameStok $opnameStok): bool
    {
        return $user->hasPermissionTo('update_opname_stok');
    }

    public function delete(User $user, OpnameStok $opnameStok): bool
    {
        return $user->hasPermissionTo('delete_opname_stok');
    }
}
