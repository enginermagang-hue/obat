<?php

namespace App\Policies;

use App\Models\PermintaanObat;
use App\Models\User;

class PermintaanObatPolicy
{
    /**
     * Permission: view_permintaan_obat
     * Super admin dan user faskes bisa melihat daftar
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_permintaan_obat');
    }

    /**
     * Permission: view_permintaan_obat
     *
     * Dinas (super_admin, admin_dinas, admin_gudang): hanya bisa melihat
     * permintaan dari puskesmas (tipe_permintaan = puskesmas_ke_dinas).
     *
     * Puskesmas: bisa melihat permintaan dari pustu di bawahnya
     * (tipe_permintaan = pustu_ke_puskesmas) DAN permintaan yang dia kirim ke dinas
     * (tipe_permintaan = puskesmas_ke_dinas).
     *
     * Pustu: hanya bisa melihat permintaan miliknya sendiri sebagai pengirim
     * (tipe_permintaan = pustu_ke_puskesmas).
     */
    public function view(User $user, PermintaanObat $permintaanObat): bool
    {
        if (! $user->hasPermissionTo('view_permintaan_obat')) {
            return false;
        }

        // Dinas: hanya permintaan dari puskesmas (bukan dari pustu)
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return $permintaanObat->tipe_permintaan === 'puskesmas_ke_dinas';
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        $userFasilitas = $user->fasilitasKesehatan;

        // Puskesmas: permintaan dari anak pustu-nya DAN permintaan yang dia kirim ke dinas
        if ($userFasilitas && $userFasilitas->tipe === 'puskesmas') {
            $pustuIds = $userFasilitas->pustu()->pluck('fasilitas_kesehatan.id')->toArray();

            $isFromPustu = $permintaanObat->tipe_permintaan === 'pustu_ke_puskesmas'
                && in_array($permintaanObat->fasilitas_pengirim_id, $pustuIds, true);

            $isFromSelf = $permintaanObat->tipe_permintaan === 'puskesmas_ke_dinas'
                && $permintaanObat->fasilitas_pengirim_id === $userFaskesId;

            return $isFromPustu || $isFromSelf;
        }

        // Pustu: hanya permintaan miliknya sendiri sebagai pengirim
        return $permintaanObat->tipe_permintaan === 'pustu_ke_puskesmas'
            && $permintaanObat->fasilitas_pengirim_id === $userFaskesId;
    }

    /**
     * Permission: create_permintaan_obat
     * HANYA user faskes yang bisa membuat permintaan
     * Super admin TIDAK bisa membuat permintaan
     */
    public function create(User $user): bool
    {
        // Super admin tidak bisa create permintaan
        if ($user->hasRole('super_admin')) {
            return false;
        }

        // User harus memiliki faskes dan permission create
        return filled($user->fasilitas_kesehatan_id)
            && $user->hasPermissionTo('create_permintaan_obat');
    }

    /**
     * Permission: update_permintaan_obat
     * Super admin: bisa mengupdate semua (untuk administrasi)
     * User faskes: hanya bisa mengupdate miliknya sendiri dan hanya jika status masih draft/menunggu_persetujuan
     */
    public function update(User $user, PermintaanObat $permintaanObat): bool
    {
        if (! $user->hasPermissionTo('update_permintaan_obat')) {
            return false;
        }

        // Super admin & admin dinas bisa mengupdate semua
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas')) {
            return true;
        }

        // User faskes: pengirim bisa edit draft/ditolak/menunggu_persetujuan
        // Fasilitas tujuan bisa edit saat menunggu_persetujuan (approve/tolak)
        $userFaskesId = $user->fasilitas_kesehatan_id;

        return filled($userFaskesId)
            && in_array($permintaanObat->status, ['draft', 'menunggu_persetujuan', 'ditolak'], true)
            && (
                $permintaanObat->fasilitas_pengirim_id === $userFaskesId
                || ($permintaanObat->status === 'menunggu_persetujuan' && $permintaanObat->fasilitas_tujuan_id === $userFaskesId)
            );
    }

    /**
     * Permission: delete_permintaan_obat
     * Super admin: bisa menghapus semua
     * User faskes: hanya bisa menghapus miliknya sendiri dan hanya jika status masih draft
     */
    public function delete(User $user, PermintaanObat $permintaanObat): bool
    {
        if (! $user->hasPermissionTo('delete_permintaan_obat')) {
            return false;
        }

        // Super admin bisa menghapus semua
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // User faskes hanya bisa menghapus miliknya sendiri jika status draft
        $userFaskesId = $user->fasilitas_kesehatan_id;

        return filled($userFaskesId)
            && $permintaanObat->fasilitas_pengirim_id === $userFaskesId
            && $permintaanObat->status === 'draft';
    }
}
