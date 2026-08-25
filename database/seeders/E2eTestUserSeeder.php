<?php

namespace Database\Seeders;

use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DistribusiObat;
use App\Models\Obat;
use App\Models\StokFaskes;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2eTestUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create puskesmas user
        $user = User::updateOrCreate(
            ['email' => 'puskesmaskupangbarat@mail.com'],
            [
                'name' => 'Petugas Puskesmas Kupang Barat',
                'password' => Hash::make('123'),
                'email_verified_at' => now(),
                'fasilitas_kesehatan_id' => 1,
            ]
        );

        if (! $user->hasRole('puskesmas')) {
            $user->assignRole('puskesmas');
        }

        $this->command->info("Created E2E test user: {$user->email}");

        // 2. Create batch_stok at puskesmas (fasilitas_id = 1)
        $obat = Obat::where('status', 'aktif')->first();

        $batch = BatchStok::updateOrCreate(
            [
                'fasilitas_id' => 1,
                'obat_id' => $obat->id,
                'batch_number' => 'E2E-BATCH-001',
            ],
            [
                'tanggal_expired' => now()->addYear(),
                'jumlah' => 100,
                'status' => 'tersedia',
                'tanggal_masuk' => now()->subDays(30),
                'harga_beli' => 1000,
            ]
        );

        // 3. Create stok_faskes at puskesmas
        StokFaskes::updateOrCreate(
            ['fasilitas_id' => 1, 'obat_id' => $obat->id],
            ['jumlah' => 100, 'stok_minimum' => 10]
        );

        // 4. Create distribusi_obat with status 'diterima'
        $distribusi = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ-E2E-TEST-001',
            'tipe_distribusi' => 'dinas_ke_puskesmas',
            'fasilitas_pengirim_id' => null,
            'fasilitas_penerima_id' => 1,
            'status' => 'diterima',
            'tanggal_kirim' => now()->subDays(7),
            'tanggal_terima' => now()->subDays(5),
            'pengirim_id' => 2,
            'penerima_id' => $user->id,
            'catatan' => 'Data untuk testing E2E retur obat',
        ]);

        // 5. Create detail_distribusi_obat
        DetailDistribusiObat::create([
            'distribusi_id' => $distribusi->id,
            'obat_id' => $obat->id,
            'batch_id' => $batch->id,
            'jumlah' => 50,
        ]);

        $this->command->info("Created test distribusi: {$distribusi->nomor_surat_jalan} with obat: {$obat->nama_obat}");
        $this->command->info("Batch: {$batch->batch_number}, Jumlah: {$batch->jumlah}");
    }
}
