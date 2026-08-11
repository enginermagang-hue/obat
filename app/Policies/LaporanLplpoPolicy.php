<?php

namespace App\Policies;

use App\Models\LaporanLplpo;
use App\Models\User;

class LaporanLplpoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_laporan_lplpo');
    }

    public function view(User $user, LaporanLplpo $laporanLplpo): bool
    {
        if (! $user->hasPermissionTo('view_laporan_lplpo')) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin_gudang', 'admin_dinas'])) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId)) {
            if ($laporanLplpo->fasilitas_id === $userFaskesId) {
                return true;
            }

            $faskes = $user->fasilitasKesehatan;
            if ($faskes && $faskes->tipe === 'puskesmas') {
                $pustuIds = $faskes->pustu()->pluck('id')->toArray();

                return in_array($laporanLplpo->fasilitas_id, $pustuIds, true);
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return false;
        }

        return filled($user->fasilitas_kesehatan_id)
            && $user->hasPermissionTo('create_laporan_lplpo');
    }

    public function update(User $user, LaporanLplpo $laporanLplpo): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return false;
        }

        if (! $user->hasPermissionTo('update_laporan_lplpo')) {
            return false;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId) && $laporanLplpo->fasilitas_id === $userFaskesId) {
            return $laporanLplpo->status === 'draft';
        }

        return false;
    }

    public function delete(User $user, LaporanLplpo $laporanLplpo): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return false;
        }

        if (! $user->hasPermissionTo('delete_laporan_lplpo')) {
            return false;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        return filled($userFaskesId)
            && $laporanLplpo->fasilitas_id === $userFaskesId
            && $laporanLplpo->status === 'draft';
    }
}
