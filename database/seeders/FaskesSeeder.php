<?php

namespace Database\Seeders;

use App\Models\FasilitasKesehatan;
use Illuminate\Database\Seeder;

class FaskesSeeder extends Seeder
{
    public function run(): void
    {
        $puskesmasList = [
            ['kode_faskes' => '51010101', 'nama' => 'Puskesmas Kupang Barat', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010102', 'nama' => 'Puskesmas Kupang Tengah', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010103', 'nama' => 'Puskesmas Kupang Timur', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010104', 'nama' => 'Puskesmas Kupang Utara', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010201', 'nama' => 'Puskesmas Amfoang Barat', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010202', 'nama' => 'Puskesmas Amfoang Selatan', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010203', 'nama' => 'Puskesmas Amfoang Timur', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010204', 'nama' => 'Puskesmas Amfoang Utara', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010301', 'nama' => 'Puskesmas Amabi Oefeto', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010302', 'nama' => 'Puskesmas Amabi Oefeto Timur', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010401', 'nama' => 'Puskesmas Nekamese', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010402', 'nama' => 'Puskesmas Semau', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010403', 'nama' => 'Puskesmas Semau Selatan', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010501', 'nama' => 'Puskesmas Taebenu', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010502', 'nama' => 'Puskesmas Amarasi', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010503', 'nama' => 'Puskesmas Amarasi Barat', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010504', 'nama' => 'Puskesmas Amarasi Selatan', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010505', 'nama' => 'Puskesmas Amarasi Timur', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010601', 'nama' => 'Puskesmas Fatuleu', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010602', 'nama' => 'Puskesmas Fatuleu Barat', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010603', 'nama' => 'Puskesmas Fatuleu Tengah', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010701', 'nama' => 'Puskesmas Takari', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010801', 'nama' => 'Puskesmas Baun', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010901', 'nama' => 'Puskesmas Sulamu', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51011001', 'nama' => 'Puskesmas Kupang Barat Daya', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51011101', 'nama' => 'Puskesmas Amfoang Barat Laut', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        ];

        $pustuList = [
            ['kode_faskes' => '51010303', 'nama' => 'Pustu Oesao', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010302', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010304', 'nama' => 'Pustu Tuapuka', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010301', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010205', 'nama' => 'Pustu Batu Cermin', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010206', 'nama' => 'Pustu Bok', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010207', 'nama' => 'Pustu Biin Toby', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010208', 'nama' => 'Pustu Oelneke', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010305', 'nama' => 'Pustu Oelububuk', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010302', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010306', 'nama' => 'Pustu Merdeka', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010301', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010307', 'nama' => 'Pustu Oelunamu', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010301', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010308', 'nama' => 'Pustu Oesao Timur', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010302', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010404', 'nama' => 'Pustu Nunsaen', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010401', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010405', 'nama' => 'Pustu Oenikun', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010401', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010406', 'nama' => 'Pustu Kolabe', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010401', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        ];

        foreach ($puskesmasList as $data) {
            FasilitasKesehatan::firstOrCreate(
                ['kode_faskes' => $data['kode_faskes']],
                $data,
            );
        }

        foreach ($pustuList as $data) {
            $indukKode = $data['puskesmas_induk_kode'];
            unset($data['puskesmas_induk_kode']);

            $puskesmasInduk = FasilitasKesehatan::where('kode_faskes', $indukKode)->first();

            $data['puskesmas_induk_id'] = $puskesmasInduk?->id;

            FasilitasKesehatan::firstOrCreate(
                ['kode_faskes' => $data['kode_faskes']],
                $data,
            );
        }
    }
}
