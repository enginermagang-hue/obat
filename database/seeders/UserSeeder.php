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
                'password' => '9\ko02.',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin Gudang',
                'email' => 'gudangfarmasi.kabkupang@gmail.com',
                'password' => '9\ko02.',
                'role' => 'admin_gudang',
            ],
            [
                'name' => 'Admin Dinas',
                'email' => 'adminfarmasi.kabkupang@gmail.com',
                'password' => '9\ko02.',
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

        // Seeder puskesmas — 1 akun per puskesmas (idempotent)
        foreach (FasilitasKesehatan::where('tipe', 'puskesmas')->get() as $faskes) {
            $email = 'puskesmas.'.$faskes->kode_faskes.'@gmail.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Petugas '.$faskes->nama,
                    'password' => '9\ko02.',
                    'fasilitas_kesehatan_id' => $faskes->id,
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole('puskesmas')) {
                $user->assignRole('puskesmas');
            }
        }
    }
}
