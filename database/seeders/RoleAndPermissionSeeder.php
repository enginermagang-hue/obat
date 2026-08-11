<?php

namespace Database\Seeders;

use App\Models\PengaturanLaporan;
use App\Services\NomorFormatService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Existing permissions (backward compatibility)
        Permission::firstOrCreate(['name' => 'view_dashboard']);
        Permission::firstOrCreate(['name' => 'manage_obat']);
        Permission::firstOrCreate(['name' => 'view_laporan']);
        Permission::firstOrCreate(['name' => 'manage_laporan']);
        Permission::firstOrCreate(['name' => 'input_laporan']);
        Permission::firstOrCreate(['name' => 'manage_pengaturan_pdf']);
        Permission::firstOrCreate(['name' => 'manage_pengaturan_nomor']);
        Permission::firstOrCreate(['name' => 'manage_backup']);

        // Resource permissions
        $resources = [
            'users',
            'roles',
            'permissions',
            'fasilitas_kesehatan',
            'obat',
            'sumber_dana',
            'stok_gudang',
            'stok_faskes',
            'batch_stok',
            'laporan_lplpo',
            'permintaan_obat',
            'distribusi_obat',
            'riwayat_stok',
            'pemakaian_obat',
            'penerimaan_stok',
            'laporan_rko',
            'neraca_tahunan',
            'sumber_dana_penggunaan',
            'alokasi_dana',
            'suppliers',
            'opname_stok',
            'retur_obat',
            'inspeksi_retur',
            'model_prediksi',
            'prediksi_kebutuhan',
            'import_data_historis',
            'pengaturan_laporan',
            'avatar_presets',
            'user_preferences',
            'activity_logs',
        ];

        $actions = ['view', 'create', 'update', 'delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}_{$resource}"]);
            }
        }

        // ──────────── Roles ────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $adminGudang = Role::firstOrCreate(['name' => 'admin_gudang']);
        $adminDinas = Role::firstOrCreate(['name' => 'admin_dinas']);
        $puskesmas = Role::firstOrCreate(['name' => 'puskesmas']);
        $pustu = Role::firstOrCreate(['name' => 'pustu']);

        $superAdmin->givePermissionTo(Permission::all());

        // ──────────── admin_gudang (Dinas) ────────────
        $adminGudang->givePermissionTo([
            'view_dashboard', 'manage_obat', 'view_laporan', 'input_laporan',
            'view_suppliers', 'create_suppliers', 'update_suppliers',
            'view_penerimaan_stok', 'create_penerimaan_stok', 'update_penerimaan_stok',
            'view_stok_gudang', 'view_stok_faskes', 'view_batch_stok',
            'view_permintaan_obat', 'update_permintaan_obat',
            'view_distribusi_obat', 'create_distribusi_obat', 'update_distribusi_obat',
            'view_riwayat_stok',
            'view_opname_stok', 'create_opname_stok', 'update_opname_stok',
            'view_laporan_lplpo', 'view_laporan_rko',
            'view_neraca_tahunan',
            'view_model_prediksi', 'view_prediksi_kebutuhan',
            'view_activity_logs',
            'manage_pengaturan_pdf',
            'view_retur_obat', 'create_retur_obat', 'update_retur_obat', 'delete_retur_obat',
            'view_inspeksi_retur', 'create_inspeksi_retur', 'update_inspeksi_retur',
            'view_pemakaian_obat',
            'manage_backup',
        ]);

        // ──────────── admin_dinas ────────────
        $adminDinas->givePermissionTo([
            'view_dashboard',
            'view_permintaan_obat', 'update_permintaan_obat',
            'view_distribusi_obat', 'update_distribusi_obat',
            'view_penerimaan_stok',
            'view_stok_gudang', 'view_stok_faskes', 'view_batch_stok',
            'view_laporan_lplpo', 'view_laporan_rko',
            'view_riwayat_stok',
            'view_opname_stok', 'update_opname_stok',
            'view_neraca_tahunan',
            'view_sumber_dana', 'create_sumber_dana', 'update_sumber_dana', 'delete_sumber_dana',
            'view_sumber_dana_penggunaan',
            'view_alokasi_dana',
            'view_model_prediksi', 'view_prediksi_kebutuhan',
            'view_activity_logs',
            'manage_pengaturan_pdf',
            'view_user_preferences', 'create_user_preferences', 'update_user_preferences', 'delete_user_preferences',
            'view_retur_obat', 'update_retur_obat',
            'view_inspeksi_retur',
            'view_pemakaian_obat',
        ]);

        // ──────────── puskesmas ────────────
        $puskesmas->givePermissionTo([
            'view_dashboard', 'input_laporan',
            'view_permintaan_obat', 'create_permintaan_obat', 'update_permintaan_obat', 'delete_permintaan_obat',
            'view_distribusi_obat', 'create_distribusi_obat', 'update_distribusi_obat',
            'view_stok_faskes',
            'view_riwayat_stok',
            'view_penerimaan_stok', 'create_penerimaan_stok', 'update_penerimaan_stok', 'delete_penerimaan_stok',
            'view_pemakaian_obat', 'create_pemakaian_obat', 'update_pemakaian_obat', 'delete_pemakaian_obat',
            'view_laporan_lplpo', 'create_laporan_lplpo', 'update_laporan_lplpo', 'delete_laporan_lplpo',
            'view_laporan_rko', 'create_laporan_rko', 'update_laporan_rko', 'delete_laporan_rko',
            'view_neraca_tahunan', 'create_neraca_tahunan', 'update_neraca_tahunan', 'delete_neraca_tahunan',
            'view_opname_stok', 'create_opname_stok', 'update_opname_stok', 'delete_opname_stok',
            'view_retur_obat', 'create_retur_obat', 'update_retur_obat', 'delete_retur_obat',
            'view_activity_logs',
            'manage_pengaturan_pdf',
        ]);

        // ──────────── pustu ────────────
        $pustu->givePermissionTo([
            'view_dashboard', 'input_laporan',
            'view_permintaan_obat', 'create_permintaan_obat', 'update_permintaan_obat',
            'view_distribusi_obat', 'update_distribusi_obat',
            'view_stok_faskes',
            'view_riwayat_stok',
            'view_penerimaan_stok', 'create_penerimaan_stok', 'update_penerimaan_stok', 'delete_penerimaan_stok',
            'view_pemakaian_obat', 'create_pemakaian_obat', 'update_pemakaian_obat', 'delete_pemakaian_obat',
            'view_laporan_lplpo', 'create_laporan_lplpo', 'update_laporan_lplpo', 'delete_laporan_lplpo',
            'view_laporan_rko', 'create_laporan_rko', 'update_laporan_rko', 'delete_laporan_rko',
            'view_neraca_tahunan', 'create_neraca_tahunan', 'update_neraca_tahunan', 'delete_neraca_tahunan',
            'view_activity_logs',
            'view_prediksi_kebutuhan',
        ]);

        // Seed default PDF settings (global)
        $this->seedDefaultPdfSettings();

        // Seed default nomor format patterns (global)
        $this->seedDefaultNomorFormats();
    }

    private function seedDefaultPdfSettings(): void
    {
        $defaults = [
            'kop_baris_1' => 'PEMERINTAH KABUPATEN KUPANG',
            'kop_baris_2' => 'DINAS KESEHATAN KABUPATEN KUPANG',
            'kop_alamat' => 'Jl. El Tari II, Kec. Kupang Tengah, Kabupaten Kupang, NTT',
            'logo_path' => '',
            'font_family' => 'DejaVu Sans',
            'font_size' => '12',
            'font_size_kop1' => '14',
            'font_size_kop2' => '16',
            'font_size_body' => '12',
            'margin_top' => '18',
            'margin_bottom' => '25',
            'margin_left' => '18',
            'margin_right' => '18',
        ];

        foreach ($defaults as $key => $value) {
            PengaturanLaporan::firstOrCreate(
                ['fasilitas_id' => null, 'grup' => 'pdf', 'key' => $key],
                ['value' => $value],
            );
        }
    }

    private function seedDefaultNomorFormats(): void
    {
        foreach (NomorFormatService::documents() as $key => $doc) {
            PengaturanLaporan::firstOrCreate(
                ['fasilitas_id' => null, 'grup' => NomorFormatService::GRUP, 'key' => $key],
                ['value' => $doc['default']],
            );
        }
    }
}
