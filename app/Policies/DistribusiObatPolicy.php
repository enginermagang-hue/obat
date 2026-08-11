<?php

namespace App\Policies;

use App\Models\DistribusiObat;
use App\Models\User;

class DistribusiObatPolicy
{
    /**
     * Permission: view_distribusi_obat
     * Semua role dengan permission bisa melihat daftar distribusi.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_distribusi_obat');
    }

    /**
     * Permission: view_distribusi_obat
     *
     * Super admin, admin dinas, admin gudang (Dinas): bisa melihat semua distribusi
     * Puskesmas: hanya distribusi yang terkait faskesnya (pengirim/penerima)
     * Pustu: hanya distribusi yang ditujukan ke faskesnya
     */
    public function view(User $user, DistribusiObat $distribusiObat): bool
    {
        if (! $user->hasPermissionTo('view_distribusi_obat')) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        // Admin gudang dari Dinas (null faskes) bisa lihat semua distribusi
        if ($user->hasRole('admin_gudang') && blank($userFaskesId)) {
            return true;
        }

        if (blank($userFaskesId)) {
            return false;
        }

        return $distribusiObat->fasilitas_pengirim_id === $userFaskesId
            || $distribusiObat->fasilitas_penerima_id === $userFaskesId;
    }

    /**
     * Permission: create_distribusi_obat
     *
     * Super admin, admin gudang (Dinas): bisa membuat
     * Puskesmas: bisa membuat untuk distribusi ke pustu
     * Pustu: TIDAK bisa membuat (harus via permintaan)
     * Admin dinas: TIDAK bisa membuat (tugas admin dinas di approve/reject)
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin_gudang')) {
            return true;
        }

        // Pustu tidak bisa membuat distribusi
        if ($user->hasRole('pustu')) {
            return false;
        }

        // Puskesmas dengan fasilitas dan permission create
        return filled($user->fasilitas_kesehatan_id)
            && $user->hasPermissionTo('create_distribusi_obat');
    }

    /**
     * Permission: update_distribusi_obat
     *
     * Super admin: bisa mengupdate semua
     * Admin gudang (Dinas, null faskes): update distribusi yang dibuatnya (draft/dalam_pengiriman)
     * Admin dinas (null faskes): update untuk oversight (setiap distribusi)
     * Puskesmas: update distribusi miliknya (draft/dalam_pengiriman)
     * Pustu: tidak bisa update (konfirmasi terima/tolak via action di halaman detail)
     */
    public function update(User $user, DistribusiObat $distribusiObat): bool
    {
        if (! $user->hasPermissionTo('update_distribusi_obat')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        // Admin gudang (Dinas, tanpa faskes): update distribusi yang dibuatnya
        if ($user->hasRole('admin_gudang') && blank($userFaskesId)) {
            return $distribusiObat->pengirim_id === $user->id
                && in_array($distribusiObat->status, ['draft', 'dalam_pengiriman'], true);
        }

        // Admin dinas (tanpa faskes): oversight, bisa update distribusi draft/dalam_pengiriman saja
        if ($user->hasRole('admin_dinas') && blank($userFaskesId)) {
            return in_array($distribusiObat->status, ['draft', 'dalam_pengiriman'], true);
        }

        if (blank($userFaskesId)) {
            return false;
        }

        // Puskesmas / role lain dengan faskes:
        // Sebagai pengirim → update draft/dalam_pengiriman
        if ($distribusiObat->fasilitas_pengirim_id === $userFaskesId) {
            return in_array($distribusiObat->status, ['draft', 'dalam_pengiriman'], true);
        }

        return false;
    }

    /**
     * Permission: delete_distribusi_obat
     *
     * Super admin: bisa menghapus semua
     * Admin gudang: hanya miliknya sendiri dan hanya jika status draft
     * Admin dinas: tidak bisa menghapus
     * User (pustu): tidak bisa menghapus
     */
    public function delete(User $user, DistribusiObat $distribusiObat): bool
    {
        if (! $user->hasPermissionTo('delete_distribusi_obat')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        // Admin gudang (Dinas, tanpa faskes): hapus distribusi yang dibuatnya sendiri
        if ($user->hasRole('admin_gudang') && blank($userFaskesId)) {
            return $distribusiObat->pengirim_id === $user->id
                && $distribusiObat->status === 'draft';
        }

        // Non super-admin lainnya: hanya hapus jika milik faskes sendiri dan status draft
        return filled($userFaskesId)
            && $distribusiObat->fasilitas_pengirim_id === $userFaskesId
            && $distribusiObat->status === 'draft';
    }
}
