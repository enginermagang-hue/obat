# Role & Permission — Dokumentasi

## 1. Tujuan

Mendefinisikan **hierarki otorisasi** aplikasi RUANG OBAT sesuai tingkatan fasilitas kesehatan (Pustu → Puskesmas → Dinas). Setiap role memiliki permission spesifik untuk mengatur akses ke sumber daya sesuai tanggung jawabnya.

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ❌ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 3. Hierarki Role

```
super_admin
├── admin_dinas (Dinas Kesehatan — tanpa faskes)
│   └── admin_gudang (Dinas — Gudang, tanpa faskes)
├── puskesmas (Puskesmas — punya faskes_id)
│   └── pustu (Pustu — punya faskes_id, induk ke Puskesmas)
```

| Role           | `fasilitas_kesehatan_id` | Sumber Data    |
| -------------- | :-----------------------: | -------------- |
| `super_admin`  | —                         | —              |
| `admin_dinas`  | `null` (Dinas)            | `users`        |
| `admin_gudang` | `null` (Dinas)            | `users`        |
| `puskesmas`    | `puskesmas`               | `users`        |
| `pustu`        | `pustu`                   | `users`        |

> **Catatan:** `admin_gudang` dan `admin_dinas` berasal dari Dinas Kesehatan — tidak memiliki `fasilitas_kesehatan_id`. Scope aksesnya global (seluruh faskes). `puskesmas` dan `pustu` terikat ke faskes masing-masing.

---

## 4. Permission per Resource

Semua resource menggunakan 4 aksi standar CRUD: `view`, `create`, `update`, `delete`.
Daftar resource:

| Resource               | Label                    |
| ---------------------- | ------------------------ |
| `users`                | Manajemen User           |
| `roles`                | Manajemen Role           |
| `permissions`          | Manajemen Permission     |
| `fasilitas_kesehatan`  | Fasilitas Kesehatan      |
| `obat`                 | Obat                     |
| `sumber_dana`          | Sumber Dana              |
| `stok_gudang`          | Stok Gudang (Dinas)      |
| `stok_faskes`          | Stok Faskes              |
| `batch_stok`           | Batch Stok               |
| `laporan_lplpo`        | LPLPO                    |
| `permintaan_obat`      | Permintaan Obat          |
| `distribusi_obat`      | Distribusi Obat          |
| `riwayat_stok`         | Riwayat Stok             |
| `pemakaian_obat`       | Pemakaian Obat           |
| `penerimaan_stok`      | Penerimaan Stok          |
| `laporan_rko`          | RKO                      |
| `neraca_tahunan`       | Neraca Tahunan           |
| `sumber_dana_penggunaan` | Penggunaan Sumber Dana |
| `suppliers`            | Pemasok                  |
| `opname_stok`          | Opname Stok              |
| `retur_obat`           | Retur Obat               |
| `inspeksi_retur`       | Inspeksi Retur           |
| `model_prediksi`       | Model Prediksi           |
| `prediksi_kebutuhan`   | Prediksi Kebutuhan       |
| `import_data_historis` | Import Data Historis     |
| `pengaturan_laporan`   | Pengaturan Laporan       |
| `avatar_presets`       | Avatar Presets           |
| `user_preferences`     | Preferensi User          |
| `activity_logs`        | Catatan Aktivitas        |

---

## 5. Permission per Role

### 5.1 `super_admin`

Semua permission (`Permission::all()`). Diimplementasikan via Spatie `Gate::before`.

### 5.2 `admin_gudang`

Manajemen gudang Dinas. Fokus pada distribusi, stok pusat, dan penerimaan.

```
view_dashboard, manage_obat, view_laporan, input_laporan
view_suppliers, create_suppliers, update_suppliers
view_penerimaan_stok, create_penerimaan_stok, update_penerimaan_stok
view_stok_gudang, view_stok_faskes, view_batch_stok
view_permintaan_obat, update_permintaan_obat
view_distribusi_obat, create_distribusi_obat, update_distribusi_obat
view_riwayat_stok
view_opname_stok, create_opname_stok, update_opname_stok
view_laporan_lplpo, view_laporan_rko
view_neraca_tahunan, create_neraca_tahunan, update_neraca_tahunan
view_model_prediksi, view_prediksi_kebutuhan
view_activity_logs
manage_pengaturan_pdf
```

### 5.3 `admin_dinas`

Oversight dan approval tingkat Dinas. Approval permintaan, monitoring distribusi, pengaturan.

```
view_dashboard
view_permintaan_obat, update_permintaan_obat
view_distribusi_obat, update_distribusi_obat
view_stok_gudang, view_stok_faskes, view_batch_stok
view_laporan_lplpo, view_laporan_rko
view_riwayat_stok
view_opname_stok, update_opname_stok
view_neraca_tahunan
view_sumber_dana, view_sumber_dana_penggunaan
view_model_prediksi, view_prediksi_kebutuhan
view_activity_logs
manage_pengaturan_pdf
view_user_preferences, create_user_preferences, update_user_preferences, delete_user_preferences
```

### 5.4 `puskesmas`

Operasional penuh di tingkat Puskesmas. Bisa membuat permintaan ke Dinas, distribusi ke Pustu, laporan LPLPO/RKO.

```
view_dashboard, input_laporan
view_permintaan_obat, create_permintaan_obat, update_permintaan_obat, delete_permintaan_obat
view_distribusi_obat, create_distribusi_obat, update_distribusi_obat
view_stok_faskes
view_riwayat_stok
view_penerimaan_stok, create_penerimaan_stok, update_penerimaan_stok, delete_penerimaan_stok
view_pemakaian_obat, create_pemakaian_obat, update_pemakaian_obat, delete_pemakaian_obat
view_laporan_lplpo, create_laporan_lplpo, update_laporan_lplpo, delete_laporan_lplpo
view_laporan_rko, create_laporan_rko, update_laporan_rko, delete_laporan_rko
view_neraca_tahunan, create_neraca_tahunan, update_neraca_tahunan
view_opname_stok, create_opname_stok, update_opname_stok, delete_opname_stok
view_retur_obat, create_retur_obat, update_retur_obat, delete_retur_obat
view_activity_logs
view_model_prediksi, view_prediksi_kebutuhan
manage_pengaturan_pdf
```

### 5.5 `pustu`

Operasional terbatas di tingkat Pustu. Hanya bisa membuat permintaan ke Puskesmas, menerima distribusi, dan mengelola laporan sendiri.

```
view_dashboard, input_laporan
view_permintaan_obat, create_permintaan_obat, update_permintaan_obat
view_distribusi_obat, update_distribusi_obat       ← hanya konfirmasi terima
view_stok_faskes
view_riwayat_stok
view_penerimaan_stok, create_penerimaan_stok, update_penerimaan_stok, delete_penerimaan_stok
view_pemakaian_obat, create_pemakaian_obat, update_pemakaian_obat, delete_pemakaian_obat
view_laporan_lplpo, create_laporan_lplpo, update_laporan_lplpo, delete_laporan_lplpo
view_laporan_rko, create_laporan_rko, update_laporan_rko, delete_laporan_rko
view_neraca_tahunan
view_activity_logs
view_prediksi_kebutuhan
```

> **Catatan:** Pustu **tidak memiliki** `create_distribusi_obat` — distribusi hanya bisa dibuat oleh Puskesmas atau admin_gudang Dinas.

---

## 6. Matrix Permission (Ringkasan)

| Resource           | Aksi | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ------------------ | :--: | :---------: | :----------: | :---------: | :-------: | :---: |
| Dashboard          | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Obat               | CRUD |      ✅     |  manage_obat |      -      |     -     |   -   |
| Suppliers          | view |      ✅     |      ✅      |      -      |     -     |   -   |
| Suppliers          | CUD  |      ✅     |      CU      |      -      |     -     |   -   |
| Stok Gudang        | view |      ✅     |      ✅      |     ✅      |     -     |   -   |
| Stok Faskes        | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Batch Stok         | view |      ✅     |      ✅      |     ✅      |     -     |   -   |
| Permintaan Obat    | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Permintaan Obat    | C    |      ✅     |      -       |      -      |    ✅     |  ✅   |
| Permintaan Obat    | U    |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Permintaan Obat    | D    |      ✅     |      -       |      -      |    ✅     |   -   |
| Distribusi Obat    | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Distribusi Obat    | C    |      ✅     |      ✅      |      -      |    ✅     |   -   |
| Distribusi Obat    | U    |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Distribusi Obat    | D    |      ✅     |      -       |      -      |     -     |   -   |
| Laporan LPLPO      | CRUD |      ✅     |      V       |      V      |    ✅     |  ✅   |
| Laporan RKO        | CRUD |      ✅     |      V       |      V      |    ✅     |  ✅   |
| Pemakaian Obat     | CRUD |      ✅     |      -       |      -      |    ✅     |  ✅   |
| Penerimaan Stok    | CRUD |      ✅     |      CU      |      -      |    ✅     |  ✅   |
| Neraca Tahunan     | V/CU |      ✅     |      ✅      |     V       |    ✅     |  V    |
| Opname Stok        | CRUD |      ✅     |      CU      |     VU      |    ✅     |   -   |
| Retur Obat         | CRUD |      ✅     |      -       |      -      |    ✅     |   -   |
| Riwayat Stok       | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Prediksi Kebutuhan | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Model Prediksi     | view |      ✅     |      ✅      |     ✅      |    ✅     |   -   |
| Activity Logs      | view |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Sumber Dana        | view |      ✅     |      -       |     ✅      |     -     |   -   |
| Pengaturan PDF     |      |      ✅     |      ✅      |     ✅      |    ✅     |   -   |

**Keterangan:**
- ✅ = full CRUD
- V = view only
- CU = create + update (no delete)
- VU = view + update
- V/CU = view + create + update (no delete)
- - = no access

---

## 7. Policy Rules per Resource

### 7.1 DistribusiObatPolicy

| Method   | Role            | Aturan                                                                    |
| -------- | --------------- | ------------------------------------------------------------------------- |
| `view`   | super_admin     | ✅ semua                                                                  |
| `view`   | admin_dinas     | ✅ semua                                                                  |
| `view`   | admin_gudang    | ✅ semua (Dinas, null faskes)                                             |
| `view`   | puskesmas/pustu | ✅ hanya distribusi terkait faskesnya (pengirim/penerima)                 |
| `create` | super_admin     | ✅                                                                        |
| `create` | admin_gudang    | ✅ (distribusi dari Dinas)                                                |
| `create` | puskesmas       | ✅ (distribusi ke Pustu)                                                  |
| `create` | pustu           | ❌ (harus via permintaan)                                                 |
| `create` | admin_dinas     | ❌                                                                        |
| `update` | super_admin     | ✅ semua                                                                  |
| `update` | admin_gudang    | ✅ hanya distribusi yang dibuatnya (`pengirim_id`), status draft/sent     |
| `update` | admin_dinas     | ✅ semua (oversight)                                                      |
| `update` | puskesmas       | ✅ sebagai pengirim: draft/sent; sebagai penerima: sent (konfirmasi terima) |
| `update` | pustu           | ✅ hanya sebagai penerima, status `dalam_pengiriman` (konfirmasi terima)  |
| `delete` | super_admin     | ✅ semua                                                                  |
| `delete` | lainnya         | ✅ hanya milik sendiri, status `draft`                                    |

### 7.2 RiwayatStokPolicy / StokFaskesPolicy

| Method | User dengan fasilitas (`fasilitas_kesehatan_id` filled) | User tanpa fasilitas (Dinas, super_admin) |
| ------ | :-----------------------------------------------------: | :---------------------------------------: |
| `view` | ✅ hanya milik faskesnya sendiri                        | ✅ semua                                  |

### 7.3 Scope Filtering (Filament)

- **StokFaskesResource**: jika user memiliki `fasilitas_kesehatan_id`, query otomatis difilter `WHERE fasilitas_id = user.fasilitas_kesehatan_id`.
- **RiwayatStoksTable**: filter default fasilitas diisi otomatis dengan faskes milik user jika user memiliki `fasilitas_kesehatan_id`.

---

## 8. File Referensi

| File                                                    | Konten                           |
| ------------------------------------------------------- | -------------------------------- |
| `database/seeders/RoleAndPermissionSeeder.php`          | Definisi role + permission       |
| `database/seeders/SimulasiTransaksiSeeder.php`          | Assignment role ke faskes users  |
| `app/Policies/DistribusiObatPolicy.php`                 | Policy distribusi                |
| `app/Policies/RiwayatStokPolicy.php`                    | Policy riwayat stok              |
| `app/Policies/StokFaskesPolicy.php`                     | Policy stok faskes               |
| `app/Filament/Resources/StokFaskes/StokFaskesResource.php` | Scope filtering stok faskes   |
| `app/Filament/Resources/RiwayatStoks/Tables/RiwayatStoksTable.php` | Default filter faskes  |

---

## 9. Catatan

1. **Spatie Permission** menggunakan `Gate::before` untuk `super_admin` — semua gate otomatis `true`.
2. Role `user` sudah **dihapus** dari sistem. Tidak ada reference di seeder, policy, atau kode.
3. `admin_gudang` dan `admin_dinas` menggunakan `fasilitas_kesehatan_id = null` karena berasal dari Dinas. Scope data-nya global.
4. `puskesmas` dan `pustu` memiliki `fasilitas_kesehatan_id` — scope data-nya terbatas ke faskes sendiri.
5. Setiap kali menjalankan seeder ulang: `php artisan db:seed --class=RoleAndPermissionSeeder`

## 10. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/Roles/Pages\CreateRole.php`
- `app/Filament/Resources/Roles/Pages\EditRole.php`
- `app/Filament/Resources/Roles/Pages\ListRoles.php`
- `app/Filament/Resources/Roles/RoleResource.php`
- `app/Filament/Resources/Roles/Schemas\RoleForm.php`
- `app/Filament/Resources/Roles/Tables\RolesTable.php`
- `app/Policies/RolePolicy.php`
