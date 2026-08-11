<?php

namespace App\Policies;

use App\Models\StokGudang;
use App\Models\User;

class StokGudangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_stok_gudang');
    }

    public function view(User $user, StokGudang $stokGudang): bool
    {
        return $user->hasPermissionTo('view_stok_gudang');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StokGudang $stokGudang): bool
    {
        return false;
    }

    public function delete(User $user, StokGudang $stokGudang): bool
    {
        return false;
    }
}
