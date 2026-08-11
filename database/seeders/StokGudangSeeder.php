<?php

namespace Database\Seeders;

use App\Models\Obat;
use App\Models\StokGudang;
use Illuminate\Database\Seeder;

class StokGudangSeeder extends Seeder
{
    /**
     * Stok awal gudang (Dinas) per 1 Juni 2024.
     * Jumlah mencakup kebutuhan 6 puskesmas untuk ~3-4 bulan.
     */
    public function run(): void
    {
        $obatList = Obat::all();

        $stokAwalMap = $this->getInitialStockMap();

        foreach ($obatList as $obat) {
            $stok = $stokAwalMap[$obat->kode_obat] ?? $stokAwalMap['default'];

            StokGudang::firstOrCreate(
                ['obat_id' => $obat->id],
                [
                    'jumlah' => $stok['jumlah'],
                    'stok_minimum' => $stok['stok_minimum'],
                ],
            );
        }
    }

    /**
     * @return array<string, array{jumlah: int, stok_minimum: int}>
     */
    private function getInitialStockMap(): array
    {
        return [
            // === Volume Tinggi ===
            '92001267' => ['jumlah' => 12_000, 'stok_minimum' => 2_000], // Parasetamol 500mg
            '92000881' => ['jumlah' => 8_000, 'stok_minimum' => 1_500],  // Amoxicillin 500mg
            '92000407' => ['jumlah' => 5_000, 'stok_minimum' => 1_000],  // Amlodipin 10mg
            '92001058' => ['jumlah' => 5_000, 'stok_minimum' => 1_000],  // Amlodipin 5mg
            '92000209' => ['jumlah' => 5_000, 'stok_minimum' => 1_000],  // Metformin 500mg
            '92000344' => ['jumlah' => 4_000, 'stok_minimum' => 800],    // Captopril 25mg
            '92000517' => ['jumlah' => 8_000, 'stok_minimum' => 1_500],  // Vitamin C 50mg
            '92000129' => ['jumlah' => 4_000, 'stok_minimum' => 800],    // Vitamin C 250mg
            '92000798' => ['jumlah' => 3_000, 'stok_minimum' => 600],    // Antasida Tablet
            '92001081' => ['jumlah' => 4_000, 'stok_minimum' => 800],    // Ibuprofen 400mg
            '92001386' => ['jumlah' => 3_000, 'stok_minimum' => 600],    // Kotrimoksazol

            // === Volume Sedang ===
            '92000146' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Albendazole 400mg
            '92000406' => ['jumlah' => 600, 'stok_minimum' => 100],      // Amoxicillin Sirup 250mg/5mL
            '92000486' => ['jumlah' => 500, 'stok_minimum' => 100],      // Amoxicillin Sirup 125mg/5mL
            '92000234' => ['jumlah' => 400, 'stok_minimum' => 80],       // Amoxicillin Sirup 100mg/5mL
            '92000185' => ['jumlah' => 500, 'stok_minimum' => 100],      // Ibuprofen Suspensi
            '92002848' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Oralit
            '92000163' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Glimepirid 2mg
            '92000914' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Furosemid 40mg
            '92003720' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Ranitidin 150mg
            '92001960' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Omeprazole 20mg
            '92000860' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Salbutamol 2mg
            '92000535' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Diclofenac 50mg
            '92000366' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Simvastatin 10mg
            '92000706' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Prednison 5mg
            '92001124' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Kalsium Laktat
            '92000201' => ['jumlah' => 2_000, 'stok_minimum' => 400],    // Zinc Sulfate
            '92000124' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Alopurinol 300mg
            '92000281' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Alopurinol 100mg
            '92000653' => ['jumlah' => 3_000, 'stok_minimum' => 500],    // Fe + Folic (tablet tambah darah)
            '92001885' => ['jumlah' => 1_500, 'stok_minimum' => 300],    // Pyridoxin 25mg

            // === Volume Rendah (Spesifik/Kronis) ===
            '92000715' => ['jumlah' => 3_000, 'stok_minimum' => 500],    // OAT FDC Kat 1 (TB)
            '92000564' => ['jumlah' => 1_500, 'stok_minimum' => 300],    // Tenofovir/Lamivudin/Efavirenz (ARV)
            '92000472' => ['jumlah' => 1_500, 'stok_minimum' => 300],    // Asiklovir 200mg
            '92000770' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Asiklovir 400mg
            '92000347' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Primakuin 25mg

            // === Topikal/Salep ===
            '92000403' => ['jumlah' => 300, 'stok_minimum' => 60],       // Mikonazol Krim
            '92000572' => ['jumlah' => 300, 'stok_minimum' => 60],       // Betametason Krim
            '92000731' => ['jumlah' => 200, 'stok_minimum' => 40],       // Gentamisin Salep Mata
            '92001095' => ['jumlah' => 200, 'stok_minimum' => 40],       // Asiklovir Krim

            // === Injeksi (Volume Rendah) ===
            '92000634' => ['jumlah' => 200, 'stok_minimum' => 40],       // Epinephrine
            '92000756' => ['jumlah' => 200, 'stok_minimum' => 40],       // Oksitosin
            '92000986' => ['jumlah' => 300, 'stok_minimum' => 60],       // Lidocain

            // === Obat Psikotropika ===
            '92000435' => ['jumlah' => 500, 'stok_minimum' => 100],      // Diazepam 5mg
            '92000445' => ['jumlah' => 300, 'stok_minimum' => 60],       // Haloperidol 5mg

            // === Vitamin Khusus ===
            '92000809' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Vitamin A 200.000 IU

            // === Sirup Rendah ===
            '92000637' => ['jumlah' => 200, 'stok_minimum' => 40],       // Albendazole Susp 200mg/5mL
            '92002856' => ['jumlah' => 200, 'stok_minimum' => 40],       // Albendazole Susp 400mg/5mL
            '92000139' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Domperidon 10mg
            '92001205' => ['jumlah' => 200, 'stok_minimum' => 40],       // Domperidon Susp
            '92002094' => ['jumlah' => 300, 'stok_minimum' => 60],       // Antasida Suspensi
            '92000465' => ['jumlah' => 500, 'stok_minimum' => 100],      // Fitomenadion 10mg
            '92000986' => ['jumlah' => 300, 'stok_minimum' => 60],       // Lidocain Injeksi
            '91000255' => ['jumlah' => 1_000, 'stok_minimum' => 200],    // Loratadin 10mg

            'default' => ['jumlah' => 500, 'stok_minimum' => 100],
        ];
    }
}
