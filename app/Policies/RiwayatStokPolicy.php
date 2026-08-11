<?php

namespace App\Policies;

use App\Models\RiwayatStok;
use App\Models\User;

class RiwayatStokPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_riwayat_stok');
    }

    public function view(User $user, RiwayatStok $riwayatStok): bool
    {
        if (! $user->hasPermissionTo('view_riwayat_stok')) {
            return false;
        }

        // Role dengan fasilitas (puskesmas/pustu) hanya lihat riwayat faskes miliknya sendiri
        if (filled($user->fasilitas_kesehatan_id)) {
            return $riwayatStok->fasilitas_id === $user->fasilitas_kesehatan_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RiwayatStok $riwayatStok): bool
    {
        return false;
    }

    public function delete(User $user, RiwayatStok $riwayatStok): bool
    {
        return false;
    }
}
