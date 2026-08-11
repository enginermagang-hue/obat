<?php

namespace Database\Seeders;

use App\Models\SumberDana;
use Illuminate\Database\Seeder;

class SumberDanaSeeder extends Seeder
{
    public function run(): void
    {
        $sumberDana = [
            // Tahun 2024
            [
                'kode' => 'DAK-2024-01',
                'nama' => 'DAK Fisik Bidang Kesehatan 2024',
                'tahun' => 2024,
                'total_anggaran' => 2_500_000_000,
                'status' => 'nonaktif',
            ],
            [
                'kode' => 'BOK-2024-01',
                'nama' => 'BOK Puskesmas 2024',
                'tahun' => 2024,
                'total_anggaran' => 1_800_000_000,
                'status' => 'nonaktif',
            ],
            [
                'kode' => 'APBD-2024-01',
                'nama' => 'APBD Pengadaan Obat 2024',
                'tahun' => 2024,
                'total_anggaran' => 3_200_000_000,
                'status' => 'nonaktif',
            ],

            // Tahun 2025
            [
                'kode' => 'DAK-2025-01',
                'nama' => 'DAK Fisik Bidang Kesehatan 2025',
                'tahun' => 2025,
                'total_anggaran' => 2_750_000_000,
                'status' => 'nonaktif',
            ],
            [
                'kode' => 'BOK-2025-01',
                'nama' => 'BOK Puskesmas 2025',
                'tahun' => 2025,
                'total_anggaran' => 1_950_000_000,
                'status' => 'nonaktif',
            ],
            [
                'kode' => 'APBD-2025-01',
                'nama' => 'APBD Pengadaan Obat 2025',
                'tahun' => 2025,
                'total_anggaran' => 3_500_000_000,
                'status' => 'nonaktif',
            ],

            // Tahun 2026
            [
                'kode' => 'DAK-2026-01',
                'nama' => 'DAK Fisik Bidang Kesehatan 2026',
                'tahun' => 2026,
                'total_anggaran' => 3_000_000_000,
                'status' => 'aktif',
            ],
            [
                'kode' => 'BOK-2026-01',
                'nama' => 'BOK Puskesmas 2026',
                'tahun' => 2026,
                'total_anggaran' => 2_100_000_000,
                'status' => 'aktif',
            ],
            [
                'kode' => 'APBD-2026-01',
                'nama' => 'APBD Pengadaan Obat 2026',
                'tahun' => 2026,
                'total_anggaran' => 3_800_000_000,
                'status' => 'aktif',
            ],
            [
                'kode' => 'DANA-DESA-2024-01',
                'nama' => 'Dana Desa Bidang Kesehatan 2024',
                'tahun' => 2024,
                'total_anggaran' => 500_000_000,
                'status' => 'nonaktif',
            ],
            [
                'kode' => 'DANA-DESA-2025-01',
                'nama' => 'Dana Desa Bidang Kesehatan 2025',
                'tahun' => 2025,
                'total_anggaran' => 600_000_000,
                'status' => 'nonaktif',
            ],
            [
                'kode' => 'DANA-DESA-2026-01',
                'nama' => 'Dana Desa Bidang Kesehatan 2026',
                'tahun' => 2026,
                'total_anggaran' => 700_000_000,
                'status' => 'aktif',
            ],
        ];

        foreach ($sumberDana as $data) {
            SumberDana::firstOrCreate(
                ['kode' => $data['kode']],
                $data,
            );
        }
    }
}
