<?php

namespace App\Policies;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanRko;
use App\Models\PengaturanLaporan;
use App\Models\User;

class LaporanRkoPolicy
{
    /**
     * Permission: view_laporan_rko
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_laporan_rko');
    }

    /**
     * Permission: view_laporan_rko
     *
     * Super admin & admin gudang: semua records
     * Admin dinas: semua records (mereka yang approve)
     * User faskes: hanya milik faskesnya sendiri
     */
    public function view(User $user, LaporanRko $laporanRko): bool
    {
        if (! $user->hasPermissionTo('view_laporan_rko')) {
            return false;
        }

        // Super admin, admin gudang, admin dinas bisa melihat semua
        if ($user->hasRole('super_admin') || $user->hasRole('admin_gudang') || $user->hasRole('admin_dinas')) {
            return true;
        }

        // User faskes: milik sendiri + milik pustu di bawahnya (jika puskesmas)
        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId) && $laporanRko->fasilitas_id === $userFaskesId) {
            return true;
        }

        if (filled($userFaskesId)) {
            $faskes = FasilitasKesehatan::find($userFaskesId);

            return $faskes?->tipe === 'puskesmas'
                && $faskes->pustu()->where('id', $laporanRko->fasilitas_id)->exists();
        }

        return false;
    }

    /**
     * Permission: create_laporan_rko
     *
     * HANYA user faskes (Puskesmas/Pustu) yang bisa membuat RKO.
     * Super admin bisa untuk testing.
     * Admin gudang & admin dinas TIDAK bisa membuat.
     *
     * Validasi tambahan:
     * - Cek apakah akses RKO dibuka oleh admin
     * - Cek periode tahun yang ditentukan admin
     * - Cek apakah sudah ada RKO untuk periode tersebut (1 RKO per faskes per tahun)
     */
    public function create(User $user): bool
    {
        if (! filled($user->fasilitas_kesehatan_id) || ! $user->hasPermissionTo('create_laporan_rko')) {
            return false;
        }

        // Super admin bypass
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Cek apakah akses RKO dibuka oleh admin
        $aksesDibuka = PengaturanLaporan::get('rko', 'akses_dibuka', $user->fasilitas_kesehatan_id);
        if ($aksesDibuka !== '1') {
            return false;
        }

        // Ambil periode tahun yang ditentukan admin
        $periodeRkoTahun = PengaturanLaporan::get('rko', 'periode_tahun', $user->fasilitas_kesehatan_id);
        if (blank($periodeRkoTahun)) {
            return false;
        }

        // Cek apakah sudah ada RKO untuk periode tersebut (1 RKO per faskes per tahun)
        return ! LaporanRko::where('fasilitas_id', $user->fasilitas_kesehatan_id)
            ->where('periode_tahun', (int) $periodeRkoTahun)
            ->exists();
    }

    /**
     * Permission: update_laporan_rko
     *
     * Super admin: semua
     * User faskes: hanya milik sendiri, hanya status draft
     * Admin dinas: approve/reject (diajukan → disetujui/ditolak)
     */
    public function update(User $user, LaporanRko $laporanRko): bool
    {
        if (! $user->hasPermissionTo('update_laporan_rko')) {
            return false;
        }

        // Admin dinas: bisa approve/reject
        if ($user->hasRole('admin_dinas')) {
            return $laporanRko->status === 'diajukan';
        }

        // Admin gudang: tidak bisa update
        if ($user->hasRole('admin_gudang')) {
            return false;
        }

        // Super admin: bisa update semua
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        // Puskesmas: bisa approve/reject RKO pustunya sendiri
        $faskes = FasilitasKesehatan::find($userFaskesId);

        if ($faskes?->tipe === 'puskesmas'
            && $laporanRko->status === 'diajukan'
            && $faskes->pustu()->where('id', $laporanRko->fasilitas_id)->exists()) {
            return true;
        }

        // User faskes: hanya milik sendiri jika draft DAN akses masih dibuka
        if ($laporanRko->fasilitas_id !== $userFaskesId || $laporanRko->status !== 'draft') {
            return false;
        }

        // Cek apakah akses RKO masih dibuka oleh admin
        $aksesDibuka = PengaturanLaporan::get('rko', 'akses_dibuka', $userFaskesId);

        return $aksesDibuka === '1';
    }

    /**
     * Permission: delete_laporan_rko
     *
     * Super admin: semua
     * User faskes: hanya milik sendiri, hanya status draft
     */
    public function delete(User $user, LaporanRko $laporanRko): bool
    {
        if (! $user->hasPermissionTo('delete_laporan_rko')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        return filled($userFaskesId)
            && $laporanRko->fasilitas_id === $userFaskesId
            && in_array($laporanRko->status, ['draft', 'diajukan']);
    }
}
