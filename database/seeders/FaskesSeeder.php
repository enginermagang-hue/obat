<?php

namespace Database\Seeders;

use App\Models\FasilitasKesehatan;
use Illuminate\Database\Seeder;

class FaskesSeeder extends Seeder
{
    public function run(): void
    {
        $puskesmasList = [
            ['kode_faskes' => '51010101', 'nama' => 'Puskesmas Baun', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010102', 'nama' => 'Puskesmas Sulamu', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010103', 'nama' => 'Puskesmas Naibonat', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010104', 'nama' => 'Puskesmas Camplong', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010201', 'nama' => 'Puskesmas Oesao', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
            ['kode_faskes' => '51010202', 'nama' => 'Puskesmas Tarus', 'tipe' => 'puskesmas', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        ];

        // $pustuList = [
        //     ['kode_faskes' => '51010303', 'nama' => 'Pustu Oesao', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010302', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010304', 'nama' => 'Pustu Tuapuka', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010301', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010205', 'nama' => 'Pustu Batu Cermin', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010206', 'nama' => 'Pustu Bok', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010207', 'nama' => 'Pustu Biin Toby', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010208', 'nama' => 'Pustu Oelneke', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010201', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010305', 'nama' => 'Pustu Oelububuk', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010302', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010306', 'nama' => 'Pustu Merdeka', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010301', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010307', 'nama' => 'Pustu Oelunamu', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010301', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010308', 'nama' => 'Pustu Oesao Timur', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010302', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010404', 'nama' => 'Pustu Nunsaen', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010401', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010405', 'nama' => 'Pustu Oenikun', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010401', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        //     ['kode_faskes' => '51010406', 'nama' => 'Pustu Kolabe', 'tipe' => 'pustu', 'puskesmas_induk_kode' => '51010401', 'alamat' => 'Kabupaten Kupang, NTT', 'status' => 'aktif'],
        // ];

        foreach ($puskesmasList as $data) {
            FasilitasKesehatan::firstOrCreate(
                ['kode_faskes' => $data['kode_faskes']],
                $data,
            );
        }

        // foreach ($pustuList as $data) {
        //     $indukKode = $data['puskesmas_induk_kode'];
        //     unset($data['puskesmas_induk_kode']);

        //     $puskesmasInduk = FasilitasKesehatan::where('kode_faskes', $indukKode)->first();

        //     $data['puskesmas_induk_id'] = $puskesmasInduk?->id;

        //     FasilitasKesehatan::firstOrCreate(
        //         ['kode_faskes' => $data['kode_faskes']],
        //         $data,
        //     );
        // }
    }
}
