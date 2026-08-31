<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'nama' => 'PT Kimia Farma Trading & Distribution',
                'alamat' => 'Jl. Veteran No. 9, Jakarta Pusat, DKI Jakarta',
                'telepon' => '021-3505678',
                'email' => 'kftd@kimiafarma.co.id',
                'npwp' => '01.234.567.8-001.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'PT Sanbe Farma',
                'alamat' => 'Jl. Industri No. 127, Cimahi, Jawa Barat',
                'telepon' => '022-6601000',
                'email' => 'corporate@sanbe.co.id',
                'npwp' => '01.345.678.9-002.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'PT Kalbe Farma Tbk',
                'alamat' => 'Jl. Pulomas Barat Blok II No. 2, Jakarta Timur',
                'telepon' => '021-47881234',
                'email' => 'distribution@kalbe.co.id',
                'npwp' => '01.456.789.0-003.000',
                'status' => 'aktif',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['nama' => $supplier['nama']],
                $supplier,
            );
        }
    }
}
