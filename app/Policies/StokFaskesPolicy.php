<?php

namespace App\Policies;

use App\Models\StokFaskes;
use App\Models\User;

class StokFaskesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_stok_faskes');
    }

    public function view(User $user, StokFaskes $stokFaskes): bool
    {
        if (! $user->hasPermissionTo('view_stok_faskes')) {
            return false;
        }

        // Role dengan fasilitas (puskesmas/pustu) hanya lihat stok faskes miliknya sendiri
        if (filled($user->fasilitas_kesehatan_id)) {
            return $stokFaskes->fasilitas_id === $user->fasilitas_kesehatan_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StokFaskes $stokFaskes): bool
    {
        return false;
    }

    public function delete(User $user, StokFaskes $stokFaskes): bool
    {
        return false;
    }
}
