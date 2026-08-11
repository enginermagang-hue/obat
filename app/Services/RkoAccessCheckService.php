<?php

namespace App\Services;

use App\Models\LaporanRko;
use App\Models\PengaturanLaporan;
use App\Models\User;

class RkoAccessCheckService
{
    /**
     * Cek apakah user (faskes) perlu buat RKO.
     * Return true jika: akses dibuka + belum ada RKO untuk periode tersebut + user adalah faskes.
     */
    public function userNeedsRko(User $user): bool
    {
        // Hanya faskes (punya fasilitas_kesehatan_id) yang perlu buat RKO
        if (blank($user->fasilitas_kesehatan_id)) {
            return false;
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

        // Cek apakah sudah ada RKO untuk periode tersebut
        return ! LaporanRko::where('fasilitas_id', $user->fasilitas_kesehatan_id)
            ->where('periode_tahun', (int) $periodeRkoTahun)
            ->exists();
    }

    /**
     * Ambil periode tahun RKO yang akan dibuat.
     */
    public function getPeriodeTahun(User $user): ?string
    {
        return PengaturanLaporan::get('rko', 'periode_tahun', $user->fasilitas_kesehatan_id);
    }

    /**
     * Ambil deadline pengisian RKO.
     */
    public function getDeadline(User $user): ?string
    {
        return PengaturanLaporan::get('rko', 'deadline', $user->fasilitas_kesehatan_id);
    }
}
