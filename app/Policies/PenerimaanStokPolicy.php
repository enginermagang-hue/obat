<?php

namespace App\Policies;

use App\Models\PenerimaanStok;
use App\Models\User;

class PenerimaanStokPolicy
{
    /**
     * Permission gate. Resource sudah menyaring per-faskes di getEloquentQuery(),
     * jadi policy hanya memastikan user memegang permission yang sesuai.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_penerimaan_stok');
    }

    /**
     * Lihat detail: super_admin, admin_dinas, admin_gudang boleh semua.
     * puskesmas/pustu hanya record milik faskesnya.
     */
    public function view(User $user, PenerimaanStok $penerimaanStok): bool
    {
        if (! $user->hasPermissionTo('view_penerimaan_stok')) {
            return false;
        }

        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;
        if (blank($userFaskesId)) {
            return false;
        }

        return $penerimaanStok->fasilitas_id === $userFaskesId;
    }

    /**
     * Buat baru: cukup permission. Distribusi scope sudah dijaga oleh form
     * (distribusi_id hanya muncul untuk user faskes yang memiliki distribusi).
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_penerimaan_stok');
    }

    /**
     * Update: semua role hanya boleh edit data yang masih draft.
     * Global admin bebas record faskes manapun, puskesmas/pustu hanya milik sendiri.
     */
    public function update(User $user, PenerimaanStok $penerimaanStok): bool
    {
        if (! $user->hasPermissionTo('update_penerimaan_stok')) {
            return false;
        }

        if ($penerimaanStok->status !== 'draft') {
            return false;
        }

        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;
        if (blank($userFaskesId)) {
            return false;
        }

        return $penerimaanStok->fasilitas_id === $userFaskesId;
    }

    /**
     * Delete: sama dengan update (hanya draft, milik faskes sendiri).
     */
    public function delete(User $user, PenerimaanStok $penerimaanStok): bool
    {
        if (! $user->hasPermissionTo('delete_penerimaan_stok')) {
            return false;
        }

        if ($penerimaanStok->status !== 'draft') {
            return false;
        }

        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;
        if (blank($userFaskesId)) {
            return false;
        }

        return $penerimaanStok->fasilitas_id === $userFaskesId;
    }

    private function isGlobalAdmin(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasRole('admin_dinas')
            || $user->hasRole('admin_gudang');
    }
}
