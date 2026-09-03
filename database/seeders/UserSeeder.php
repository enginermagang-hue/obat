<?php

namespace Database\Seeders;

use App\Models\FasilitasKesehatan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'instalasifarmasi.kabkupang@gmail.com',
                'password' => 'user123',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin Gudang',
                'email' => 'gudangfarmasi.kabkupang@gmail.com',
                'password' => 'user123',
                'role' => 'admin_gudang',
            ],
            [
                'name' => 'Admin Dinas',
                'email' => 'adminfarmasi.kabkupang@gmail.com',
                'password' => 'user123',
                'role' => 'admin_dinas',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        // Seeder puskesmas — akun custom per puskesmas (idempotent)
        $puskesmasAccounts = [
            ['kode' => '51010101', 'nama' => 'Puskesmas Baun', 'email' => 'farmasipkmbaun@gmail.com', 'password' => 'Baun@2026'],
            ['kode' => '51010103', 'nama' => 'Puskesmas Naibonat', 'email' => 'farmasipkmnaibonat@gmail.com', 'password' => 'Naibonat@2026'],
            ['kode' => '51010104', 'nama' => 'Puskesmas Camplong', 'email' => 'farmasipkmcamplong@gmail.com', 'password' => 'Camplong@2026'],
            ['kode' => '51010201', 'nama' => 'Puskesmas Oesao', 'email' => 'farmasipkmoesao@gmail.com', 'password' => 'Oesao@2026'],
            ['kode' => '51010202', 'nama' => 'Puskesmas Tarus', 'email' => 'farmasipkmtarus02@gmail.com', 'password' => 'Tarus@2026'],
            ['kode' => '51010102', 'nama' => 'Puskesmas Sulamu', 'email' => 'farmasipkmsulamu@gmail.com', 'password' => 'Sulamu@2026'],
        ];

        foreach ($puskesmasAccounts as $akun) {
            $faskes = FasilitasKesehatan::where('kode_faskes', $akun['kode'])->first();

            if (! $faskes) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $akun['email']],
                [
                    'name' => 'Petugas '.$akun['nama'],
                    'password' => $akun['password'],
                    'fasilitas_kesehatan_id' => $faskes->id,
                    'email_verified_at' => now(),
                ],
            );

            // Sinkronkan faskes & password jika akun sudah ada
            $user->update([
                'fasilitas_kesehatan_id' => $faskes->id,
                'password' => $akun['password'],
            ]);

            $user->syncRoles('puskesmas');
        }
    }
}
