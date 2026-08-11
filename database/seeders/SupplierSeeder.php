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
            [
                'nama' => 'PT Dexa Medica',
                'alamat' => 'Jl. Raya Serang Km 18, Kawasan Industri Manis, Tangerang',
                'telepon' => '021-5912345',
                'email' => 'info@dexamedica.com',
                'npwp' => '01.567.890.1-004.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'PT Mersifarma Tirmaku Farmindo',
                'alamat' => 'Jl. Raya Jakarta-Bogor Km 42, Sukamaju, Depok',
                'telepon' => '021-8745678',
                'email' => 'admin@mersifarma.co.id',
                'npwp' => '01.678.901.2-005.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'PT Phapros Tbk',
                'alamat' => 'Jl. Simongan No. 89, Semarang, Jawa Tengah',
                'telepon' => '024-7604567',
                'email' => 'phapros@phapros.co.id',
                'npwp' => '01.789.012.3-006.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'PT Indofarma Global Medika',
                'alamat' => 'Jl. Indofarma No. 1, Citeureup, Bogor',
                'telepon' => '021-8751234',
                'email' => 'igm@indofarma.co.id',
                'npwp' => '01.890.123.4-007.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'CV Bina Medika',
                'alamat' => 'Jl. Diponegoro No. 45, Bandung, Jawa Barat',
                'telepon' => '022-4201234',
                'email' => 'binamedika@cbn.net.id',
                'npwp' => '02.123.456.7-008.000',
                'status' => 'aktif',
            ],
            [
                'nama' => 'UD Sinar Sehat',
                'alamat' => 'Jl. Pasar Besar No. 12, Surabaya, Jawa Timur',
                'telepon' => '031-5345678',
                'email' => 'sinarsehat@yahoo.com',
                'npwp' => '03.234.567.8-009.000',
                'status' => 'nonaktif',
            ],
            [
                'nama' => 'PT Enseval Medika Prima Tbk',
                'alamat' => 'Jl. Pulo Gadung No. 88, Jakarta Timur',
                'telepon' => '021-45861234',
                'email' => 'enseval@enseval.co.id',
                'npwp' => '01.345.678.9-010.000',
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
