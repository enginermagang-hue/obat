<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@mail.com',
                'password' => '123',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin Gudang',
                'email' => 'admin_gudang@mail.com',
                'password' => '123',
                'role' => 'admin_gudang',
            ],
            [
                'name' => 'Admin Dinas',
                'email' => 'admin_dinas@mail.com',
                'password' => '123',
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

            $user->assignRole($role);
        }
    }
}
