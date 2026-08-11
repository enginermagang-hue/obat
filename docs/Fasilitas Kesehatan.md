# Fasilitas Kesehatan — Dokumentasi Fitur

## 1. Tujuan

Mengelola **master data fasilitas kesehatan** (Puskesmas dan Pustu) yang menjadi entitas utama dalam hierarki distribusi obat. Setiap user terikat ke satu faskes, dan setiap transaksi (permintaan, distribusi, pemakaian, stok) dicatat berdasarkan faskes asal.

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 3. Hierarki

```
Dinas Kesehatan (tidak ada di tabel faskes — hanya role)
  └── Puskesmas (tipe: puskesmas, puskesmas_induk_id: null)
       └── Pustu (tipe: pustu, puskesmas_induk_id: mengarah ke Puskesmas)
```

| Tipe        | `puskesmas_induk_id` | Memiliki Stok | Bikin Permintaan ke |
| ----------- | :------------------: | :-----------: | ------------------- |
| Puskesmas   | `null`               | Stok Faskes   | Dinas (via admin_dinas) |
| Pustu       | ID Puskesmas induk   | Stok Faskes   | Puskesmas induk     |

## 4. Detail Teknis

### Model & Relasi

**Model:** `App\Models\FasilitasKesehatan`  
**Table:** `fasilitas_kesehatan`  

**Fillable Fields:**
- `kode_faskes` — Kode unik faskes (contoh: `PKM-001`, `PST-001`)
- `nama` — Nama fasilitas kesehatan
- `tipe` — ENUM: `puskesmas`, `pustu`
- `puskesmas_induk_id` — FK ke diri sendiri (nullable, hanya diisi Pustu)
- `alamat` — Alamat lengkap
- `kecamatan` — Kecamatan
- `kabupaten` — Kabupaten/Kota
- `telepon` — Nomor telepon (nullable)
- `kepala_faskes` — Nama kepala faskes (nullable)
- `status` — ENUM: `aktif`, `nonaktif`

**Relasi:**

| Method              | Type     | Target                     |
| ------------------- | -------- | -------------------------- |
| `users()`           | HasMany  | `User`                     |
| `puskesmasInduk()`  | BelongsTo| `FasilitasKesehatan` (self) |
| `pustu()`           | HasMany  | `FasilitasKesehatan` (self) |
| `modelPrediksi()`   | HasMany  | `ModelPrediksi`            |
| `prediksiKebutuhan()` | HasMany | `PrediksiKebutuhan`        |
| `stokFaskes()`      | HasMany  | `StokFaskes`               |

### Form Schema (`FasilitasKesehatanForm.php`)

| Field                  | Type        | Keterangan                                   |
| ---------------------- | ----------- | -------------------------------------------- |
| `kode_faskes`          | TextInput   | Required, unique, max 255                    |
| `nama`                 | TextInput   | Required, max 255                            |
| `tipe`                 | Select      | Puskesmas / Pustu (live)                     |
| `puskesmas_induk_id`   | Select      | Hidden jika tipe=puskesmas, required jika pustu |
| `alamat`               | Textarea    | Required                                     |
| `kecamatan`            | TextInput   | Required                                     |
| `kabupaten`            | TextInput   | Required                                     |
| `telepon`              | TextInput   | Tel input, nullable                          |
| `kepala_faskes`        | TextInput   | Nullable                                     |
| `status`               | Select      | Aktif / Nonaktif, default: aktif             |

### Tabel (`FasilitasKesehatansTable.php`)

| Kolom                      | Type   | Keterangan                             |
| -------------------------- | ------ | -------------------------------------- |
| `kode_faskes`              | Text   | Sortable, searchable                   |
| `nama`                     | Text   | Sortable, searchable                   |
| `tipe`                     | Badge  | `success`=Puskesmas, `info`=Pustu      |
| `puskesmasInduk.nama`      | Text   | Toggleable, hidden default             |
| `kecamatan`                | Text   | Sortable, searchable                   |
| `kabupaten`                | Text   | Sortable, searchable                   |
| `users_count`              | Count  | Jumlah user terdaftar                  |
| `status`                   | Badge  | `success`=aktif, `danger`=nonaktif     |

### Hak Akses

| Permission                        | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| --------------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_fasilitas_kesehatan`        |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create_fasilitas_kesehatan`      |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |
| `update_fasilitas_kesehatan`      |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |
| `delete_fasilitas_kesehatan`      |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |

> Hanya `super_admin` yang bisa membuat, mengedit, atau menghapus data faskes. Role lain hanya view.

### Aturan Bisnis

1. `kode_faskes` bersifat unique — tidak bisa duplikat
2. Pustu wajib memiliki `puskesmas_induk_id` yang mengarah ke Puskesmas
3. Puskesmas tidak bisa memiliki `puskesmas_induk_id`
4. Faskes nonaktif tidak muncul di form transaksi (permintaan, distribusi, dll)
5. Satu user hanya bisa terikat ke satu faskes (`fasilitas_kesehatan_id`)

## 5. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/FasilitasKesehatans/FasilitasKesehatanResource.php`
- `app/Filament/Resources/FasilitasKesehatans/Pages\ListFasilitasKesehatans.php`
- `app/Filament/Resources/FasilitasKesehatans/Schemas\FasilitasKesehatanForm.php`
- `app/Filament/Resources/FasilitasKesehatans/Tables\FasilitasKesehatansTable.php`
- `app/Models/FasilitasKesehatan.php`
- `app/Policies/FasilitasKesehatanPolicy.php`
