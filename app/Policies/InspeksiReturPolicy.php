<?php

namespace App\Policies;

use App\Models\InspeksiRetur;
use App\Models\User;

class InspeksiReturPolicy
{
    private function userCanAccess(User $user): bool
    {
        if (! $user->hasPermissionTo('view_inspeksi_retur')) {
            return false;
        }

        return ! $user->hasRole('pustu');
    }

    public function viewAny(User $user): bool
    {
        return $this->userCanAccess($user);
    }

    public function view(User $user, InspeksiRetur $inspeksiRetur): bool
    {
        return $this->userCanAccess($user);
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('pustu')) {
            return false;
        }

        if ($user->hasRole(['admin_dinas', 'super_admin'])) {
            return false;
        }

        return $user->hasPermissionTo('create_inspeksi_retur');
    }

    public function update(User $user, InspeksiRetur $inspeksiRetur): bool
    {
        if ($user->hasRole('pustu')) {
            return false;
        }

        if (! $user->hasPermissionTo('update_inspeksi_retur')) {
            return false;
        }

        if ($user->hasRole(['admin_dinas', 'super_admin'])) {
            return false;
        }

        if ($user->hasRole('admin_gudang')) {
            return true;
        }

        if ($user->hasRole('puskesmas')) {
            return $inspeksiRetur->retur?->fasilitas_penerima_id === $user->fasilitas_kesehatan_id;
        }

        return false;
    }

    public function delete(User $user, InspeksiRetur $inspeksiRetur): bool
    {
        return false;
    }
}
