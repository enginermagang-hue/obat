<?php

namespace App\Policies;

use App\Models\NeracaTahunan;
use App\Models\User;

class NeracaTahunanPolicy
{
    /**
     * Permission: view_neraca_tahunan
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_neraca_tahunan');
    }

    /**
     * Permission: view_neraca_tahunan
     *
     * Super admin, admin gudang, admin dinas: semua records
     * User faskes: hanya milik faskesnya sendiri (atau pustu dibawahnya jika puskesmas)
     */
    public function view(User $user, NeracaTahunan $neracaTahunan): bool
    {
        if (! $user->hasPermissionTo('view_neraca_tahunan')) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('admin_gudang') || $user->hasRole('admin_dinas')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId)) {
            if ($neracaTahunan->fasilitas_id === $userFaskesId) {
                return true;
            }

            $faskes = $user->fasilitasKesehatan;
            if ($faskes && $faskes->tipe === 'puskesmas') {
                $pustuIds = $faskes->pustu()->pluck('id')->toArray();

                return in_array($neracaTahunan->fasilitas_id, $pustuIds, true);
            }
        }

        return false;
    }

    /**
     * Permission: create_neraca_tahunan
     *
     * Hanya user faskes (puskesmas/pustu) yang boleh membuat neraca milik faskesnya.
     */
    public function create(User $user): bool
    {
        return filled($user->fasilitas_kesehatan_id)
            && $user->hasPermissionTo('create_neraca_tahunan');
    }

    /**
     * Permission: update_neraca_tahunan
     *
     * Hanya user faskes yang boleh mengupdate neraca miliknya sendiri, dan hanya jika status draft.
     */
    public function update(User $user, NeracaTahunan $neracaTahunan): bool
    {
        if (! $user->hasPermissionTo('update_neraca_tahunan')) {
            return false;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId) && $neracaTahunan->fasilitas_id === $userFaskesId) {
            return $neracaTahunan->status === 'draft';
        }

        return false;
    }

    /**
     * Permission: delete_neraca_tahunan
     *
     * Super admin: bisa hapus semua (override darurat)
     * User faskes: hanya hapus milik sendiri, hanya jika status draft
     */
    public function delete(User $user, NeracaTahunan $neracaTahunan): bool
    {
        if (! $user->hasPermissionTo('delete_neraca_tahunan')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        return filled($userFaskesId)
            && $neracaTahunan->fasilitas_id === $userFaskesId
            && $neracaTahunan->status === 'draft';
    }
}
