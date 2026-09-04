<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AvatarPresetSeeder::class,
            FaskesSeeder::class,
            UserSeeder::class,
            SupplierSeeder::class,
            ObatSeeder::class,
            // SumberDanaSeeder::class,
            // StokGudangSeeder::class,
            // SimulasiTransaksiSeeder::class,
        ]);
    }
}
