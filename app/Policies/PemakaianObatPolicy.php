<?php

namespace App\Policies;

use App\Models\PemakaianObat;
use App\Models\User;

class PemakaianObatPolicy
{
    /**
     * Permission: view_pemakaian_obat
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_pemakaian_obat');
    }

    /**
     * Permission: view_pemakaian_obat
     *
     * Super admin / admin dinas / admin gudang: semua record
     * Puskesmas / pustu: hanya record milik faskesnya
     */
    public function view(User $user, PemakaianObat $pemakaianObat): bool
    {
        if (! $user->hasPermissionTo('view_pemakaian_obat')) {
            return false;
        }

        if ($user->hasRole(['super_admin', 'admin_dinas', 'admin_gudang'])) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        return $pemakaianObat->fasilitas_id === $userFaskesId;
    }

    /**
     * Permission: create_pemakaian_obat
     *
     * Hanya user yang terikat dengan fasilitas (puskesmas/pustu) yang bisa membuat pemakaian.
     */
    public function create(User $user): bool
    {
        if (! $user->hasPermissionTo('create_pemakaian_obat')) {
            return false;
        }

        return filled($user->fasilitas_kesehatan_id);
    }

    /**
     * Permission: update_pemakaian_obat
     *
     * Super admin: semua
     * Puskesmas/pustu: hanya record milik faskesnya, dan hanya untuk tanggal_pemakaian = hari ini.
     */
    public function update(User $user, PemakaianObat $pemakaianObat): bool
    {
        if (! $user->hasPermissionTo('update_pemakaian_obat')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        return $pemakaianObat->fasilitas_id === $userFaskesId
            && $pemakaianObat->tanggal_pemakaian?->isToday();
    }

    /**
     * Permission: delete_pemakaian_obat
     *
     * Super admin: semua
     * Puskesmas/pustu: hanya record milik faskesnya, dan hanya untuk tanggal_pemakaian = hari ini.
     */
    public function delete(User $user, PemakaianObat $pemakaianObat): bool
    {
        if (! $user->hasPermissionTo('delete_pemakaian_obat')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        return $pemakaianObat->fasilitas_id === $userFaskesId
            && $pemakaianObat->tanggal_pemakaian?->isToday();
    }
}
