# Skema Database Sistem Stok Obat Dinas Kesehatan

## Ringkasan

Sistem ini mengelola distribusi obat dari Gudang Dinas Kesehatan ke Puskesmas, dan dari Puskesmas ke Pustu. Terdapat 4 peran pengguna: **Super Admin**, **Admin Gudang**, **Admin Dinas**, dan **User** (Puskesmas/Pustu). Stok masuk ke gudang dicatat melalui **Penerimaan Stok** yang mencakup pembelian (dari supplier), hibah, stok awal, dan penyesuaian manual. Sistem juga dilengkapi **AI Prediction** menggunakan ANN (Jaringan Saraf Tiruan, PHP murni) untuk memprediksi kebutuhan obat mendatang, serta fitur **Retur Obat** dengan proses karantina dan inspeksi.

---

## Diagram Relasi

```
fasilitas_kesehatan ──────────────────────────────┐
  │ 1:N                                            │
  ▼                                                │
users ────────────────────────────────────────────┘
  │ 1:N (puskesmas_induk_id)                    │
  │                                              │
  ├─ 1:N ───► stok_faskes                        │
  ├─ 1:N ───► permintaan_obat (pengirim)         │
  ├─ 1:N ───► permintaan_obat (tujuan, nullable) │
  ├─ 1:N ───► distribusi_obat (pengirim)         │
  ├─ 1:N ───► distribusi_obat (penerima)         │
  ├─ 1:N ───► retur_obat (pengirim, nullable)    │
  ├─ 1:N ───► retur_obat (penerima, nullable)    │
  ├─ 1:N ───► riwayat_stok                       │
  ├─ 1:N ───► pemakaian_obat                     │
  ├─ 1:N ───► laporan_lplpo                      │
  ├─ 1:N ───► laporan_rko                        │
  ├─ 1:N ───► batch_stok (faskes)                │
  ├─ 1:N ───► sumber_dana_penggunaan             │
  ├─ 1:N ───► model_prediksi                     │
  ├─ 1:N ───► prediksi_kebutuhan                 │
  ├─ 1:N ───► neraca_tahunan                     │
  └─ 1:N ───► exports                            │

suppliers ─┬─ 1:N ──► penerimaan_stok
           └─ 1:N ──► retur_obat (nullable)

obat ─┬─ 1:N ──► stok_gudang
      ├─ 1:N ──► stok_faskes
      ├─ 1:N ──► batch_stok
      ├─ 1:N ──► detail_penerimaan_stok
      ├─ 1:N ──► detail_permintaan_obat
      ├─ 1:N ──► detail_distribusi_obat
      ├─ 1:N ──► riwayat_stok
      ├─ 1:N ──► detail_pemakaian_obat
      ├─ 1:N ──► detail_lplpo
      ├─ 1:N ──► detail_rko
      ├─ 1:N ──► detail_opname_stok
      ├─ 1:N ──► detail_neraca_tahunan
      ├─ 1:N ──► model_prediksi
      └─ 1:N ──► prediksi_kebutuhan

permintaan_obat ── 1:N ──► detail_permintaan_obat
       │
       ├─ 1:N ──► distribusi_obat
       ├─ N:1 ◄── fasilitas_kesehatan (tujuan, nullable)
       └─ N:1 ◄── laporan_lplpo (lplpo_id nullable)

distribusi_obat ── 1:N ──► detail_distribusi_obat
       │
       ├─ 1:N ──► penerimaan_stok (nullable, tipe=distribusi)
       └─ N:1 ──► penerimaan_stok (penerimaan_stok_id, nullable)

laporan_lplpo ── 1:N ──► detail_lplpo
       │
       └─ 1:N ──► detail_rko (lplpo_referensi_id)

laporan_rko ── 1:N ──► detail_rko

sumber_dana ── 1:N ──► sumber_dana_penggunaan
              └─ 1:N ──► penerimaan_stok

penerimaan_stok ── 1:N ──► detail_penerimaan_stok
         │
         ├─ N:1 ◄── suppliers
         ├─ N:1 ◄── fasilitas_kesehatan
         ├─ N:1 ◄── sumber_dana (nullable)
         ├─ N:1 ◄── distribusi_obat (distribusi_id, nullable)
         └─ 1:N ──► batch_stok

opname_stok ── 1:N ──► detail_opname_stok

pemakaian_obat ── 1:N ──► detail_pemakaian_obat

retur_obat ── 1:N ──► detail_retur_obat
       │
       ├─ N:1 ◄── distribusi_obat (nullable)
       ├─ N:1 ◄── penerimaan_stok (penerimaan_id, nullable)
       ├─ N:1 ◄── suppliers (supplier_id, nullable)
       ├─ N:1 ◄── fasilitas_kesehatan (pengirim, nullable)
       ├─ N:1 ◄── fasilitas_kesehatan (penerima, nullable)
       └─ 1:N ──► inspeksi_retur

batch_stok (gudang & faskes, fasilitas_id nullable, penerimaan_id nullable, sumber_dana_id nullable)

model_prediksi ── 1:N ──► prediksi_kebutuhan

neraca_tahunan ── 1:N ──► detail_neraca_tahunan
       │
       └─ N:1 ◄── fasilitas_kesehatan (nullable)

pengaturan_laporan (fasilitas_id nullable: NULL = Dinas, filled = per faskes)

import_data_historis (tracking proses import Excel LPLPO/RKO lama)

users ── 1:1 ──► user_preferences

avatar_presets ── N:1 ◄── user_preferences (avatar_path)

exports (Filament action export tracking)
imports (Filament action import tracking)
failed_import_rows (Filament import error tracking)
notifications (Laravel notification tracking)
```

---

## Tabel

### 1. `users` (Bawaan Laravel)

Tabel pengguna sistem. Relasi dengan role menggunakan Spatie Laravel Permission.

| Kolom                    | Tipe          | Nullable | Keterangan                                          |
| ------------------------ | ------------- | -------- | --------------------------------------------------- |
| id                       | BIGINT (PK)   |          | Primary key                                         |
| name                     | VARCHAR       |          | Nama lengkap                                        |
| email                    | VARCHAR       |          | Email (unique)                                      |
| email_verified_at        | TIMESTAMP     | ✓        | Waktu verifikasi email                              |
| password                 | VARCHAR       |          | Password (hashed)                                   |
| remember_token           | VARCHAR       | ✓        | Token remember me                                   |
| fasilitas_kesehatan_id   | BIGINT (FK)   | ✓        | FK ke `fasilitas_kesehatan.id`. Satu user per faskes|
| created_at               | TIMESTAMP     | ✓        |                                                     |
| updated_at               | TIMESTAMP     | ✓        |                                                     |

**Role yang digunakan:**
- `super_admin` — Akses penuh ke semua fitur (sebelumnya: `admin_utama`)
- `admin_gudang` — Mengelola stok gudang, penerimaan stok, supplier, dan distribusi
- `admin_dinas` — Menyetujui permintaan obat dari faskes, mengelola sumber dana, melihat laporan penerimaan
- `puskesmas` — Operasional penuh di tingkat Puskesmas (membuat permintaan, distribusi ke Pustu, laporan LPLPO/RKO)
- `pustu` — Operasional terbatas di tingkat Pustu (permintaan ke Puskesmas, pemakaian, laporan)

---

### 2. `fasilitas_kesehatan`

Data fasilitas kesehatan (Puskesmas dan Pustu). Pustu memiliki relasi ke Puskesmas induknya melalui `puskesmas_induk_id`.

| Kolom                | Tipe              | Nullable | Keterangan                                          |
| -------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                   | BIGINT (PK)       |          | Primary key                                         |
| kode_faskes          | VARCHAR           |          | Kode unik faskes (contoh: `PKM-001`, `PST-001`)     |
| nama                 | VARCHAR           |          | Nama fasilitas kesehatan                            |
| tipe                 | ENUM              |          | `puskesmas`, `pustu`                                |
| puskesmas_induk_id   | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. Hanya diisi Pustu   |
| alamat               | TEXT              |          | Alamat lengkap                                      |
| kecamatan            | VARCHAR           |          | Kecamatan                                           |
| kabupaten            | VARCHAR           |          | Kabupaten/Kota                                      |
| telepon              | VARCHAR           | ✓        | Nomor telepon                                       |
| kepala_faskes        | VARCHAR           | ✓        | Nama kepala fasilitas kesehatan                     |
| status               | ENUM              |          | `aktif`, `nonaktif`                                 |
| created_at           | TIMESTAMP         | ✓        |                                                     |
| updated_at           | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Puskesmas: `puskesmas_induk_id = NULL`
- Pustu: `puskesmas_induk_id` mengarah ke Puskesmas induknya
- Unique constraint: `kode_faskes`

---

### 3. `obat`

Master data obat.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| kode_obat        | VARCHAR           |          | Kode unik obat                                      |
| nama_obat        | VARCHAR           |          | Nama dagang obat                                    |
| nama_generik     | VARCHAR           | ✓        | Nama generik obat                                   |
| kategori         | VARCHAR           |          | Nama kategori (contoh: `Antibiotik`, `Analgesik`)   |
| ven_kategori     | CHAR(1)           | ✓        | Kategori VEN (`V` Vital, `E` Esensial, `N` Non-esensial) |
| satuan           | VARCHAR           |          | Nama satuan (Tablet, Kapsul, Botol, Ampul, dll)     |
| kekuatan         | VARCHAR           | ✓        | Kekuatan obat (contoh: `500mg`, `10ml`)             |
| bentuk_sediaan   | ENUM              |          | `tablet`, `kapsul`, `sirup`, `salep`, `injeksi`, `drop`, `inhaler`, `suppositoria` |
| produsen         | VARCHAR           | ✓        | Nama produsen                                       |
| kemasan          | VARCHAR           | ✓        | Deskripsi kemasan (contoh: `10 tablet/strip`)       |
| harga_satuan     | DECIMAL(12,2)     | ✓        | Harga per satuan                                    |
| status           | ENUM              |          | `aktif`, `nonaktif`                                 |
| metode_stok      | VARCHAR(4)        |          | Metode pengelolaan stok: `fefo` (default), `fifo`   |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `kode_obat`
- `kategori` dan `satuan` adalah **kolom string** (denormalisasi) — bukan foreign key ke tabel master
- `ven_kategori` digunakan untuk analisis VEN (Vital-Esensial-Non-esensial) di RKO
- `harga_satuan` = **harga referensi** (diupdate otomatis dari `batch_stok.harga_beli` batch terbaru yang masuk)
- Digunakan sebagai acuan awal `detail_rko.harga_perkiraan` saat membuat RKO
- `metode_stok` menentukan algoritma pemilihan batch saat distribusi (FEFO/FIFO)

---

### 4. `sumber_dana`

Sumber pendanaan untuk perencanaan obat (RKO) dan realisasi pembelian. Dikelola oleh **Admin Dinas** via Filament Resource di **Master Data**.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| kode             | VARCHAR           |          | Kode unik (contoh: `APBD`, `BOK`, `BOK_P2`)         |
| nama             | VARCHAR           |          | Nama sumber dana (contoh: `Bantuan Operasional Kesehatan`) |
| tahun            | INT               |          | Tahun anggaran                                      |
| total_anggaran   | DECIMAL(14,2)     |          | Pagu anggaran                                       |
| status           | ENUM              |          | `aktif`, `nonaktif`                                 |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `kode`, `tahun` (kombinasi unik)
- Dikelola oleh **Admin Dinas**
- Faskes memilih sumber dana yang tersedia saat membuat RKO

---

### 5. `stok_gudang`

Stok agregat obat di gudang dinas kesehatan. Kolom `jumlah` adalah total dari semua batch yang tersedia.

| Kolom            | Tipe          | Nullable | Keterangan                              |
| ---------------- | ------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)   |          | Primary key                             |
| obat_id          | BIGINT (FK)   |          | FK ke `obat.id`                         |
| jumlah           | INT           |          | Jumlah stok saat ini (agregat)          |
| stok_minimum     | INT           |          | Batas minimum stok (alert jika di bawah)|
| created_at       | TIMESTAMP     | ✓        |                                         |
| updated_at       | TIMESTAMP     | ✓        |                                         |

**Catatan:**
- Unique constraint: `obat_id` (satu obat = satu baris stok gudang)
- `jumlah` = `SUM(batch_stok.jumlah)` WHERE `fasilitas_id IS NULL AND status = 'tersedia'`

---

### 6. `stok_faskes`

Stok agregat obat di fasilitas kesehatan (Puskesmas dan Pustu). Kolom `jumlah` adalah total dari semua batch yang tersedia.

| Kolom            | Tipe          | Nullable | Keterangan                              |
| ---------------- | ------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)   |          | Primary key                             |
| fasilitas_id     | BIGINT (FK)   |          | FK ke `fasilitas_kesehatan.id`          |
| obat_id          | BIGINT (FK)   |          | FK ke `obat.id`                         |
| jumlah           | INT           |          | Jumlah stok saat ini (agregat)          |
| stok_minimum     | INT           |          | Batas minimum stok (alert jika di bawah)|
| created_at       | TIMESTAMP     | ✓        |                                         |
| updated_at       | TIMESTAMP     | ✓        |                                         |

**Catatan:**
- Unique constraint: `fasilitas_id`, `obat_id` (kombinasi unik)
- Digunakan oleh **Puskesmas dan Pustu**
- `jumlah` = `SUM(batch_stok.jumlah)` WHERE `fasilitas_id = X AND status = 'tersedia'`

---

### 7. `batch_stok`

Tracking stok per batch dengan nomor batch dan tanggal kedaluwarsa. Digunakan untuk **gudang dan faskes** (Puskesmas/Pustu). Batch dibuat otomatis saat penerimaan stok dikonfirmasi.

| Kolom                | Tipe              | Nullable | Keterangan                                          |
| -------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                   | BIGINT (PK)       |          | Primary key                                         |
| fasilitas_id         | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. **NULL = gudang**   |
| obat_id              | BIGINT (FK)       |          | FK ke `obat.id`                                     |
| batch_number         | VARCHAR           |          | Nomor batch dari produsen/supplier                  |
| tanggal_expired      | DATE              |          | Tanggal kedaluwarsa obat                            |
| jumlah               | INT               |          | Jumlah stok pada batch ini                          |
| status               | ENUM              |          | `tersedia`, `karantina`, `expired`, `dimusnahkan`   |
| tanggal_masuk        | DATE              |          | Tanggal batch masuk ke gudang/faskes                |
| penerimaan_id        | BIGINT (FK)       | ✓        | FK ke `penerimaan_stok.id`. Batch dari penerimaan   |
| sumber_dana_id       | BIGINT (FK)       | ✓        | FK ke `sumber_dana.id`. Diisi otomatis dari penerimaan |
| harga_beli           | DECIMAL(12,2)     | ✓        | Harga beli per satuan (dari detail_penerimaan_stok) |
| created_at           | TIMESTAMP         | ✓        |                                                     |
| updated_at           | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- `fasilitas_id = NULL` → batch stok di **gudang**
- `fasilitas_id ≠ NULL` → batch stok di **faskes** (Puskesmas/Pustu)
- `stok_gudang.jumlah` = `SUM(batch_stok.jumlah)` WHERE `fasilitas_id IS NULL AND status = 'tersedia'`
- `stok_faskes.jumlah` = `SUM(batch_stok.jumlah)` WHERE `fasilitas_id = X AND status = 'tersedia'`
- **FEFO (First Expired First Out)**: distribusi prioritaskan batch dengan `tanggal_expired` paling dekat
- Query expired: `SELECT * FROM batch_stok WHERE tanggal_expired <= NOW() + INTERVAL 30 DAY AND status = 'tersedia'`
- `harga_beli` = **harga beli aktual** (dari `detail_penerimaan_stok.harga_satuan`). Saat batch baru masuk, `obat.harga_satuan` diupdate otomatis dari harga batch terbaru
- `penerimaan_id` menggantikan kolom `supplier` (varchar) yang sebelumnya — data supplier sekarang via relasi ke `penerimaan_stok.supplier_id`

---

### 8. `permintaan_obat`

Permintaan obat antar faskes atau dari faskes ke dinas.

| Kolom                    | Tipe              | Nullable | Keterangan                                          |
| ------------------------ | ----------------- | -------- | --------------------------------------------------- |
| id                       | BIGINT (PK)       |          | Primary key                                         |
| nomor_permintaan         | VARCHAR           |          | Nomor unik permintaan (contoh: `REQ/2026/001`)      |
| fasilitas_pengirim_id    | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id` (yang meminta)       |
| fasilitas_tujuan_id      | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id` (NULL = Dinas, untuk `puskesmas_ke_dinas`) |
| tipe_permintaan          | ENUM              |          | `pustu_ke_puskesmas`, `puskesmas_ke_dinas`          |
| lplpo_id                 | BIGINT (FK)       | ✓        | FK ke `laporan_lplpo.id`. Jika permintaan berasal dari LPLPO |
| status                   | ENUM              |          | `draft`, `menunggu_persetujuan`, `disetujui`, `ditolak`, `sedang_didistribusi`, `diterima`, `dibatalkan` |
| tanggal_permintaan       | DATE              |          | Tanggal permintaan dibuat                           |
| tanggal_disetujui        | DATE              | ✓        | Tanggal permintaan disetujui                        |
| tanggal_ditolak          | DATE              | ✓        | Tanggal permintaan ditolak                          |
| tanggal_dikirim          | DATE              | ✓        | Tanggal distribusi dikirim                          |
| tanggal_diterima         | DATE              | ✓        | Tanggal permintaan diterima                         |
| disetujui_oleh           | BIGINT (FK)       | ✓        | FK ke `users.id` (user yang menyetujui)             |
| catatan                  | TEXT              | ✓        | Catatan tambahan                                    |
| alasan_penolakan         | TEXT              | ✓        | Alasan penolakan permintaan                         |
| created_at               | TIMESTAMP         | ✓        |                                                     |
| updated_at               | TIMESTAMP         | ✓        |                                                     |

**Alur Status:**

**Pustu → Puskesmas:**
1. Pustu membuat permintaan → `draft`
2. Pustu mengajukan → `menunggu_persetujuan`
3. Puskesmas menyetujui → `disetujui` / menolak → `ditolak`
4. Puskesmas mendistribusikan → `sedang_didistribusi`
5. Pustu menerima → `diterima`

**Puskesmas → Dinas:**
1. Puskesmas membuat permintaan → `draft`
2. Puskesmas mengajukan → `menunggu_persetujuan`
3. Admin Dinas menyetujui → `disetujui` / menolak → `ditolak`
4. Admin Gudang mendistribusikan → `sedang_didistribusi`
5. Puskesmas menerima → `diterima`

**Catatan:**
- Unique constraint: `nomor_permintaan`
- `lplpo_id` diisi jika permintaan dibuat dari LPLPO yang sudah disetujui
- `tipe_permintaan = 'puskesmas_ke_dinas'` → `fasilitas_tujuan_id = NULL` (tujuan = Dinas, tidak ada di faskes)
- `tipe_permintaan = 'pustu_ke_puskesmas'` → `fasilitas_tujuan_id` diisi (tujuan = Puskesmas induk)

---

### 9. `detail_permintaan_obat`

Detail item obat dalam setiap permintaan.

| Kolom                | Tipe          | Nullable | Keterangan                              |
| -------------------- | ------------- | -------- | --------------------------------------- |
| id                   | BIGINT (PK)   |          | Primary key                             |
| permintaan_id        | BIGINT (FK)   |          | FK ke `permintaan_obat.id`              |
| obat_id              | BIGINT (FK)   |          | FK ke `obat.id`                         |
| jumlah_diminta       | INT           |          | Jumlah yang diminta                     |
| jumlah_disetujui     | INT           | ✓        | Jumlah yang disetujui (bisa < diminta)  |
| jumlah_dikirim       | INT           | ✓        | Jumlah yang dikirim                     |
| jumlah_diterima      | INT           | ✓        | Jumlah yang diterima                    |
| catatan              | TEXT          | ✓        | Catatan per item                        |

---

### 10. `distribusi_obat`

Proses pengiriman/distribusi obat yang telah disetujui.

| Kolom                        | Tipe              | Nullable | Keterangan                                          |
| ---------------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                           | BIGINT (PK)       |          | Primary key                                         |
| nomor_surat_jalan            | VARCHAR           |          | Nomor surat jalan (contoh: `SJ/2026/001`)           |
| permintaan_id                | BIGINT (FK)       | ✓        | FK ke `permintaan_obat.id` (nullable, distribusi bisa mandiri) |
| tipe_distribusi              | ENUM              |          | `dinas_ke_puskesmas`, `puskesmas_ke_pustu`          |
| fasilitas_pengirim_id        | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. NULL = Gudang Dinas |
| fasilitas_penerima_id        | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id` (penerima)           |
| status                       | ENUM              |          | `draft`, `dalam_pengiriman`, `diterima`, `ditolak`  |
| tanggal_kirim                | DATE              | ✓        | Tanggal pengiriman (NULL saat draft)                |
| tanggal_terima               | DATE              | ✓        | Tanggal penerimaan                                  |
| pengirim_id                  | BIGINT (FK)       |          | FK ke `users.id` (user yang mengirim)               |
| penerima_id                  | BIGINT (FK)       | ✓        | FK ke `users.id` (user yang menerima)               |
| penerimaan_stok_id           | BIGINT (FK)       | ✓        | FK ke `penerimaan_stok.id`. Auto-created saat diterima |
| catatan                      | TEXT              | ✓        | Catatan pengiriman                                  |
| created_at                   | TIMESTAMP         | ✓        |                                                     |
| updated_at                   | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_surat_jalan`
- `permintaan_id` nullable — distribusi bisa dibuat tanpa permintaan (distribusi mandiri)
- `penerimaan_stok_id` — otomatis dibuat saat distribusi diterima (tipe penerimaan = `distribusi`)
- `tipe_distribusi = 'dinas_ke_puskesmas'` → `fasilitas_pengirim_id = NULL` (obat dari Gudang Dinas)
- `tipe_distribusi = 'puskesmas_ke_pustu'` → `fasilitas_pengirim_id` diisi (obat dari stok Puskesmas)
- **Alur Status:**
  1. Admin Gudang membuat distribusi → `draft`
  2. Admin Gudang konfirmasi kirim → `dalam_pengiriman`
  3. Faskes penerima konfirmasi → `diterima` / `ditolak`

---

### 11. `detail_distribusi_obat`

Detail item obat dalam setiap distribusi. Mendukung **split allocation** (satu obat bisa dikirim dari beberapa batch berbeda).

| Kolom            | Tipe          | Nullable | Keterangan                              |
| ---------------- | ------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)   |          | Primary key                             |
| distribusi_id    | BIGINT (FK)   |          | FK ke `distribusi_obat.id`              |
| obat_id          | BIGINT (FK)   |          | FK ke `obat.id`                         |
| batch_id         | BIGINT (FK)   |          | FK ke `batch_stok.id` (batch yang dikirim) |
| jumlah           | INT           |          | Jumlah yang didistribusikan dari batch ini |

**Catatan:**
- **Split Allocation:** Jika satu permintaan obat (misal: 80 tablet) tidak bisa dipenuhi oleh satu batch saja, sistem akan memecah menjadi beberapa baris di tabel ini (contoh: 50 dari Batch A, 30 dari Batch B).
- **FEFO:** Batch dipilih otomatis berdasarkan `tanggal_expired` paling dekat.

---

### 12. `riwayat_stok`

Log semua perubahan stok (masuk, keluar, distribusi, rusak, expired, opname, dll).

| Kolom              | Tipe              | Nullable | Keterangan                                          |
| ------------------ | ----------------- | -------- | --------------------------------------------------- |
| id                 | BIGINT (PK)       |          | Primary key                                         |
| fasilitas_id       | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. NULL = gudang       |
| obat_id            | BIGINT (FK)       |          | FK ke `obat.id`                                     |
| tipe               | ENUM              |          | `masuk`, `keluar`, `distribusi_masuk`, `distribusi_keluar`, `rusak`, `hilang`, `expired`, `opname`, `penyesuaian` |
| jumlah             | INT               |          | Jumlah perubahan (positif = masuk, negatif = keluar)|
| stok_sebelum       | INT               |          | Stok sebelum perubahan                              |
| stok_sesudah       | INT               |          | Stok setelah perubahan                              |
| referensi_type     | VARCHAR           | ✓        | Model referensi (PermintaanObat, DistribusiObat, dll)|
| referensi_id       | BIGINT            | ✓        | ID dari model referensi                             |
| user_id            | BIGINT (FK)       |          | FK ke `users.id` (user yang melakukan perubahan)    |
| keterangan         | TEXT              | ✓        | Keterangan tambahan                                 |
| tanggal            | DATE              |          | Tanggal perubahan                                   |
| created_at         | TIMESTAMP         | ✓        |                                                     |

---

### 13. `pemakaian_obat`

Header pencatatan pemakaian obat per pelayanan di faskes. Detail per obat ada di `detail_pemakaian_obat`. Digunakan untuk laporan detail dan sebagai sumber data training AI prediction.

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| nomor_pemakaian        | VARCHAR(50)       | ✓        | Nomor unik pemakaian (contoh: `PKM/2026/001`)       |
| fasilitas_id           | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id`                      |
| tanggal_pemakaian      | DATE              |          | Tanggal obat dipakai/diberikan                      |
| jenis_pelayanan        | ENUM              |          | `rawat_jalan`, `rawat_inap`, `uks`, `posyandu`, `pusling`, `gigi`, `laboratorium`, `apotek`, `lainnya` |
| nama_pasien            | VARCHAR           | ✓        | Nama pasien yang dilayani                           |
| no_rekam_medis         | VARCHAR(50)       | ✓        | Nomor rekam medis pasien                            |
| diagnosa_kode          | VARCHAR           | ✓        | Kode diagnosa ICD-10 (contoh: `J06.9`, `K02.9`)     |
| user_id                | BIGINT (FK)       |          | FK ke `users.id` (petugas yang mencatat)            |
| catatan                | TEXT              | ✓        | Catatan tambahan                                    |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_pemakaian`
- Header berisi info pelayanan & pasien; detail per obat ada di `detail_pemakaian_obat`
- Setiap detail pemakaian otomatis membuat entri di `riwayat_stok` dengan `tipe = 'keluar'`
- Data ini menjadi **sumber utama training AI prediction**

---

### 14. `detail_pemakaian_obat`

Detail item obat dalam setiap pencatatan pemakaian. Mendukung FEFO (batch paling cepat expired diprioritaskan).

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| pemakaian_id     | BIGINT (FK)       |          | FK ke `pemakaian_obat.id` (cascade)                |
| obat_id          | BIGINT (FK)       |          | FK ke `obat.id`                                     |
| batch_id         | BIGINT (FK)       | ✓        | FK ke `batch_stok.id`. Batch yang digunakan (FEFO)  |
| jumlah           | INT UNSIGNED      |          | Jumlah obat yang dipakai                            |
| dosis            | VARCHAR(100)      | ✓        | Dosis obat (contoh: `3x1`)                         |
| satuan_dosis     | VARCHAR(50)       | ✓        | Satuan dosis (contoh: `tablet`, `ml`)              |
| catatan          | TEXT              | ✓        | Catatan per item                                    |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- `batch_id` diisi otomatis berdasarkan FEFO (batch paling cepat expired)
- Setiap pencatatan detail otomatis membuat entri di `riwayat_stok` dengan `tipe = 'keluar'`

---

### 15. `laporan_lplpo`

Laporan Pemakaian dan Lembar Permintaan Obat (LPLPO).

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| nomor_laporan          | VARCHAR           |          | Nomor unik laporan (contoh: `LPLPO/2026/01/001`)    |
| fasilitas_id           | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id`                      |
| periode_bulan          | INT               |          | Bulan laporan (1-12)                                |
| periode_tahun          | INT               |          | Tahun laporan                                       |
| tipe_pengajuan         | ENUM              |          | `pustu_ke_puskesmas`, `puskesmas_ke_dinas`          |
| jenis_pengajuan        | ENUM              |          | `rutin`, `tambahan` (default `rutin`)               |
| status                 | ENUM              |          | `draft`, `diajukan`, `disetujui`, `ditolak`         |
| tanggal_pembuatan      | DATE              |          | Tanggal laporan dibuat                              |
| tanggal_pengajuan      | DATE              | ✓        | Tanggal laporan diajukan                            |
| tanggal_disetujui      | DATE              | ✓        | Tanggal laporan disetujui                           |
| dibuat_oleh            | BIGINT (FK)       |          | FK ke `users.id`                                    |
| disetujui_oleh         | BIGINT (FK)       | ✓        | FK ke `users.id`                                    |
| catatan                | TEXT              | ✓        | Catatan tambahan                                    |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_laporan`
- Unique constraint: `fasilitas_id`, `periode_bulan`, `periode_tahun`, `jenis_pengajuan` (kombinasi unik)
- `tipe_pengajuan` menentukan alur approval:
  - `pustu_ke_puskesmas` → diajukan ke Puskesmas induk
  - `puskesmas_ke_dinas` → diajukan ke Admin Dinas
- `jenis_pengajuan`: `rutin` = laporan bulanan reguler, `tambahan` = laporan tambahan di bulan yang sama

---

### 16. `detail_lplpo`

Detail item obat dalam laporan LPLPO.

| Kolom                    | Tipe                  | Nullable | Keterangan                                      |
| ------------------------ | --------------------- | -------- | ----------------------------------------------- |
| id                       | BIGINT (PK)           |          | Primary key                                     |
| lplpo_id                 | BIGINT (FK)           |          | FK ke `laporan_lplpo.id`                        |
| obat_id                  | BIGINT (FK)           |          | FK ke `obat.id`                                 |
| stok_awal                | INT                   |          | Stok awal periode                               |
| jumlah_masuk             | INT                   |          | Total obat masuk selama periode                 |
| jumlah_keluar            | INT                   |          | Total obat keluar selama periode                |
| sisa_stok                | INT                   |          | Sisa stok akhir periode                         |
| stok_optimum             | INT                   | ✓        | Stok optimum yang disarankan                    |
| permintaan_selanjutnya   | INT                   |          | Usulan permintaan periode berikutnya            |
| sudah_diminta            | BOOLEAN               |          | Default `false`, `true` jika sudah jadi permintaan |
| permintaan_id            | BIGINT (FK)           | ✓        | FK ke `permintaan_obat.id` (jika sudah diminta) |
| keterangan               | TEXT                  | ✓        | Keterangan per item                             |

---

### 17. `laporan_rko`

Rencana Kebutuhan Obat (RKO).

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| nomor_rko              | VARCHAR           |          | Nomor unik RKO (contoh: `RKO/2026/001`)             |
| fasilitas_id           | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id`                      |
| periode_tahun          | INT               |          | Tahun perencanaan                                   |
| status                 | ENUM              |          | `draft`, `diajukan`, `disetujui`, `ditolak`         |
| tanggal_pembuatan      | DATE              |          | Tanggal RKO dibuat                                  |
| tanggal_pengajuan      | DATE              | ✓        | Tanggal RKO diajukan                                |
| tanggal_disetujui      | DATE              | ✓        | Tanggal RKO disetujui                               |
| total_anggaran         | DECIMAL(14,2)     | ✓        | Akumulasi total dari detail_rko (usulan × harga)    |
| dibuat_oleh            | BIGINT (FK)       |          | FK ke `users.id`                                    |
| disetujui_oleh         | BIGINT (FK)       | ✓        | FK ke `users.id`                                    |
| catatan                | TEXT              | ✓        | Catatan tambahan                                    |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_rko`
- Unique constraint: `fasilitas_id`, `periode_tahun` (kombinasi unik)
- `total_anggaran` dihitung otomatis dari `SUM(detail_rko.total_harga)`

---

### 18. `detail_rko`

Detail item obat dalam laporan RKO.

| Kolom                          | Tipe                  | Nullable | Keterangan                                      |
| ------------------------------ | --------------------- | -------- | ----------------------------------------------- |
| id                             | BIGINT (PK)           |          | Primary key                                     |
| rko_id                         | BIGINT (FK)           |          | FK ke `laporan_rko.id`                          |
| obat_id                        | BIGINT (FK)           |          | FK ke `obat.id`                                 |
| pemakaian_tahun_sebelumnya     | INT                   |          | Total pemakaian tahun sebelumnya                |
| rata_rata_pemakaian_bulanan    | INT                   |          | Rata-rata pemakaian per bulan                   |
| stok_akhir                     | INT                   |          | Stok akhir saat perencanaan                     |
| kebutuhan_tahunan              | INT                   |          | Kebutuhan obat untuk satu tahun                 |
| usulan                         | INT                   |          | Usulan jumlah yang dibutuhkan                   |
| harga_perkiraan                | DECIMAL(12,2)         | ✓        | Harga satuan perkiraan                          |
| total_harga                    | DECIMAL(14,2)         | ✓        | usulan × harga_perkiraan                        |
| buffer_stock_persen            | DECIMAL(5,2)          |          | Persentase buffer stock (default `0.00`)        |
| buffer_stok_qty               | INT                   |          | Jumlah buffer stock dalam satuan (default `0`)  |
| total_kebutuhan                | INT                   |          | Total kebutuhan = kebutuhan_tahunan + buffer (default `0`) |
| ven_kategori                   | VARCHAR(1)            | ✓        | Kategori VEN obat (`V`, `E`, `N`)               |
| rencana_kebutuhan              | INT                   |          | Rencana kebutuhan final setelah buffer & VEN (default `0`) |
| abc_kategori                   | CHAR(1)               | ✓        | Kategori ABC obat (`A`, `B`, `C`)              |
| lplpo_referensi_id             | BIGINT (FK)           | ✓        | FK ke `laporan_lplpo.id` (opsional, referensi dari LPLPO mana) |
| prediksi_id                    | BIGINT (FK)           | ✓        | FK ke `prediksi_kebutuhan.id` (jika diisi dari AI) |
| keterangan                     | TEXT                  | ✓        | Keterangan per item                             |

**Catatan:**
- `harga_perkiraan` diisi otomatis dari `obat.harga_satuan` saat membuat RKO, tapi **bisa diedit** jika ada harga kontrak/e-katalog yang berbeda
- `total_harga` dihitung otomatis: `usulan × harga_perkiraan`
- `prediksi_id` diisi jika usulan berasal dari prediksi AI
- `ven_kategori` dari `obat.ven_kategori`, digunakan untuk analisis VEN di RKO
- `abc_kategori` dari analisis ABC (konsumsi nilai), digunakan untuk prioritas pengadaan
- `buffer_stock_persen` dan `buffer_stok_qty` untuk perhitungan safety stock

---

### 19. `sumber_dana_penggunaan`

Tracking penggunaan anggaran dari sumber dana. Dicatat saat RKO disetujui (perencanaan) dan saat penerimaan stok dikonfirmasi (realisasi).

| Kolom                    | Tipe              | Nullable | Keterangan                                          |
| ------------------------ | ----------------- | -------- | --------------------------------------------------- |
| id                       | BIGINT (PK)       |          | Primary key                                         |
| sumber_dana_id           | BIGINT (FK)       |          | FK ke `sumber_dana.id`                              |
| rko_id                   | BIGINT (FK)       | ✓        | FK ke `laporan_rko.id` (nullable jika bukan dari RKO)|
| fasilitas_id             | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id`                      |
| tipe                     | ENUM              |          | `perencanaan`, `realisasi`                          |
| jumlah_obat              | INT               |          | Jumlah obat                                         |
| total_biaya              | DECIMAL(14,2)     |          | Total biaya                                         |
| tanggal                  | DATE              |          | Tanggal transaksi                                   |
| keterangan               | TEXT              | ✓        | Keterangan                                          |
| created_at               | TIMESTAMP         | ✓        |                                                     |
| updated_at               | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- `tipe = 'perencanaan'` → saat RKO disetujui (anggaran direncanakan)
- `tipe = 'realisasi'` → saat penerimaan stok dikonfirmasi (anggaran terealisasi via pembelian dari supplier)
- `rko_id` diisi hanya untuk `tipe = 'perencanaan'` (nullable untuk realisasi karena tidak selalu terkait RKO)
- Sisa anggaran = `sumber_dana.total_anggaran - SUM(sumber_dana_penggunaan.total_biaya)` WHERE `sumber_dana_id = X`

---

### 20. `suppliers`

Data supplier obat. Digunakan sebagai referensi saat mencatat penerimaan stok tipe **pembelian** dan retur ke supplier.

| Kolom       | Tipe          | Nullable | Keterangan                              |
| ----------- | ------------- | -------- | --------------------------------------- |
| id          | BIGINT (PK)   |          | Primary key                             |
| nama        | VARCHAR       |          | Nama supplier (unique)                  |
| alamat      | TEXT          | ✓        | Alamat lengkap                          |
| telepon     | VARCHAR(50)   | ✓        | Nomor telepon                           |
| email       | VARCHAR       | ✓        | Alamat email                            |
| npwp        | VARCHAR(50)   | ✓        | NPWP supplier                           |
| status      | ENUM          |          | `aktif`, `nonaktif` (default `aktif`)   |
| created_at  | TIMESTAMP     | ✓        |                                         |
| updated_at  | TIMESTAMP     | ✓        |                                         |

**Catatan:**
- Hanya supplier dengan `status = 'aktif'` yang muncul di form penerimaan stok
- `nama` bersifat unique

---

### 21. `penerimaan_stok`

Pencatatan semua stok yang masuk ke gudang, mencakup pembelian, hibah, stok awal, penyesuaian, distribusi, dan manual. Satu tabel untuk semua tipe penerimaan.

| Kolom                | Tipe              | Nullable | Keterangan                                          |
| -------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                   | BIGINT (PK)       |          | Primary key                                         |
| nomor_penerimaan     | VARCHAR           |          | Nomor unik penerimaan (contoh: `PO/2026/001`)       |
| tipe                 | ENUM              |          | `pembelian`, `hibah`, `stok_awal`, `penyesuaian`, `distribusi`, `manual` |
| supplier_id          | BIGINT (FK)       | ✓        | FK ke `suppliers.id`. Diisi jika tipe = `pembelian` |
| nomor_po             | VARCHAR           | ✓        | Nomor PO (purchase order). Hanya untuk pembelian    |
| nomor_invoice        | VARCHAR           | ✓        | Nomor invoice. Hanya untuk pembelian                |
| tanggal_penerimaan   | DATE              |          | Tanggal penerimaan stok                             |
| fasilitas_id         | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. NULL = gudang       |
| user_id              | BIGINT (FK)       |          | FK ke `users.id` (petugas yang mencatat)            |
| status               | ENUM              |          | `draft`, `dikonfirmasi`, `dibatalkan` (default `draft`) |
| catatan              | TEXT              | ✓        | Catatan tambahan                                    |
| total_biaya          | DECIMAL(12,2)     | ✓        | Akumulasi total dari detail (auto-calculated)       |
| sumber_dana_id       | BIGINT (FK)       | ✓        | FK ke `sumber_dana.id`. Diisi hanya untuk `pembelian` |
| distribusi_id        | BIGINT (FK)       | ✓        | FK ke `distribusi_obat.id`. Auto-created saat distribusi diterima |
| created_at           | TIMESTAMP         | ✓        |                                                     |
| updated_at           | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_penerimaan`
- `supplier_id` hanya diisi jika `tipe = 'pembelian'`
- `sumber_dana_id` hanya diisi jika `tipe = 'pembelian'`
- `distribusi_id` diisi otomatis jika `tipe = 'distribusi'` (penerimaan stok di faskes dari distribusi gudang/puskesmas)
- `tipe = 'manual'` untuk pencatatan masuk stok secara manual tanpa dokumen resmi
- Saat status diubah ke `dikonfirmasi`, sistem otomatis:
  1. Membuat `batch_stok` untuk setiap item (termasuk cascade `sumber_dana_id`)
  2. Mencatat `riwayat_stok` dengan `tipe = 'masuk'`
  3. Menambah `stok_gudang.jumlah`
  4. Mencatat `sumber_dana_penggunaan` dengan `tipe = 'realisasi'` (jika ada sumber dana)

---

### 22. `detail_penerimaan_stok`

Detail item obat dalam setiap penerimaan stok.

| Kolom             | Tipe              | Nullable | Keterangan                                          |
| ----------------- | ----------------- | -------- | --------------------------------------------------- |
| id                | BIGINT (PK)       |          | Primary key                                         |
| penerimaan_id     | BIGINT (FK)       |          | FK ke `penerimaan_stok.id` (cascade)                |
| obat_id           | BIGINT (FK)       |          | FK ke `obat.id` (cascade)                           |
| batch_number      | VARCHAR(100)      | ✓        | Nomor batch dari produsen (nullable untuk tipe distribusi/manual) |
| tanggal_expired   | DATE              |          | Tanggal kedaluwarsa                                 |
| jumlah            | INT               |          | Jumlah yang diterima (default `0`)                  |
| harga_satuan      | DECIMAL(12,2)     | ✓        | Harga per satuan (nullable untuk hibah/stok awal/distribusi)   |
| sub_total         | DECIMAL(12,2)     | ✓        | jumlah × harga_satuan (auto-calculated)             |
| keterangan        | TEXT              | ✓        | Catatan per item                                    |
| created_at        | TIMESTAMP         | ✓        |                                                     |
| updated_at        | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Foreign keys cascade on delete: menghapus penerimaan akan menghapus detail-nya
- Untuk stok awal, hibah, dan distribusi, `harga_satuan` bisa dikosongkan
- `batch_number` nullable — untuk tipe distribusi/manual batch number bisa tidak diisi

---

### 23. `opname_stok`

Pencatatan opname (stock opname) untuk menyesuaikan stok sistem dengan stok fisik, atau menambahkan stok baru ke sistem.

| Kolom              | Tipe              | Nullable | Keterangan                                          |
| ------------------ | ----------------- | -------- | --------------------------------------------------- |
| id                 | BIGINT (PK)       |          | Primary key                                         |
| nomor_opname       | VARCHAR           |          | Nomor unik opname (contoh: `OPN/2026/001`)          |
| tipe               | ENUM              | ✓        | `penyesuaian`, `stok_awal`, `stok_baru`. Default `penyesuaian`   |
| fasilitas_id       | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. NULL = gudang       |
| tanggal_opname     | DATE              |          | Tanggal opname dilakukan                            |
| status             | ENUM              |          | `draft`, `proses`, `selesai`                        |
| user_id            | BIGINT (FK)       |          | FK ke `users.id` (pelaksana opname)                 |
| catatan            | TEXT              | ✓        | Catatan opname                                      |
| created_at         | TIMESTAMP         | ✓        |                                                     |
| updated_at         | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_opname`
- **Tipe opname:**
  - `penyesuaian` — Menyesuaikan stok yang sudah ada di gudang/faskes. Stok sistem diambil dari database, selisih bisa positif atau negatif. Batch tracking opsional.
  - `stok_awal` — Pencatatan awal stok existing saat sistem pertama kali digunakan. Mirip `stok_baru` tapi khusus untuk migrasi data awal.
  - `stok_baru` — Menambahkan stok baru ke sistem (barang yang belum tercatat). Stok sistem = 0, selisih selalu positif (stok_fisik). Batch tracking **wajib** diisi.

---

### 24. `detail_opname_stok`

Detail item obat dalam opname stok.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| opname_id        | BIGINT (FK)       |          | FK ke `opname_stok.id`                              |
| obat_id          | BIGINT (FK)       |          | FK ke `obat.id`                                     |
| batch_id         | BIGINT (FK)       | ✓        | FK ke `batch_stok.id`. Batch yang di-opname         |
| stok_sistem      | INT               |          | Stok menurut sistem                                 |
| stok_fisik       | INT               |          | Stok hasil perhitungan fisik                        |
| selisih          | INT               |          | Selisih (stok_fisik - stok_sistem)                  |
| batch_number     | VARCHAR(100)      | ✓        | Nomor batch (wajib untuk stok_baru/stok_awal, opsional untuk penyesuaian) |
| tanggal_expired  | DATE              | ✓        | Tanggal kedaluwarsa batch (wajib untuk stok_baru/stok_awal, opsional untuk penyesuaian) |
| keterangan       | TEXT              | ✓        | Keterangan per item                                 |

**Catatan:**
- `stok_sistem` = 0 untuk tipe `stok_baru` (barang baru belum tercatat di sistem)
- `selisih` = `stok_fisik - stok_sistem` — selalu positif untuk `stok_baru`
- `batch_number` + `tanggal_expired` wajib diisi jika tipe opname = `stok_baru`
- Saat opname diproses (`selesai`):
  - **Selisih > 0 & batch_number diisi** → `BatchStok` di-create/di-update
  - **Selisih < 0** → hanya adjust stok agregat (tanpa batch, karena pengurangan)

---

### 25. `retur_obat`

Pencatatan pengembalian obat dari puskesmas ke gudang, pustu ke puskesmas, atau gudang ke supplier.

| Kolom                    | Tipe              | Nullable | Keterangan                                          |
| ------------------------ | ----------------- | -------- | --------------------------------------------------- |
| id                       | BIGINT (PK)       |          | Primary key                                         |
| nomor_retur              | VARCHAR           |          | Nomor unik retur (contoh: `RET/2026/001`)           |
| distribusi_id            | BIGINT (FK)       | ✓        | FK ke `distribusi_obat.id` (jika retur dari distribusi) |
| penerimaan_id            | BIGINT (FK)       | ✓        | FK ke `penerimaan_stok.id` (jika retur dari penerimaan) |
| fasilitas_pengirim_id    | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id` (NULL = Gudang, untuk `gudang_ke_supplier`) |
| fasilitas_penerima_id    | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id` (NULL = Gudang/Supplier, tidak ada di faskes) |
| supplier_id              | BIGINT (FK)       | ✓        | FK ke `suppliers.id` (supplier tujuan untuk `gudang_ke_supplier`) |
| tipe_retur               | ENUM              |          | `puskesmas_ke_gudang`, `pustu_ke_puskesmas`, `gudang_ke_supplier` |
| alasan                   | ENUM              |          | `expired`, `rusak`, `kelebihan_stok`, `salah_kirim`, `recall`, `near_expiry`, `lainnya` |
| alasan_lainnya           | TEXT              | ✓        | Keterangan manual jika alasan = `lainnya`           |
| status                   | ENUM              |          | `draft`, `menunggu_approval`, `disetujui`, `ditolak`, `dalam_pengiriman`, `diterima`, `selesai` |
| tanggal_retur            | DATE              |          | Tanggal retur dibuat                                |
| tanggal_disetujui        | DATE              | ✓        | Tanggal retur disetujui                             |
| tanggal_ditolak          | DATE              | ✓        | Tanggal retur ditolak                               |
| tanggal_dikirim          | DATE              | ✓        | Tanggal obat dikirim kembali                        |
| tanggal_diterima         | DATE              | ✓        | Tanggal obat diterima tujuan                        |
| disetujui_oleh           | BIGINT (FK)       | ✓        | FK ke `users.id` (user yang menyetujui)             |
| catatan                  | TEXT              | ✓        | Catatan tambahan                                    |
| created_at               | TIMESTAMP         | ✓        |                                                     |
| updated_at               | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_retur`
- `penerimaan_id` diisi jika retur terkait dengan penerimaan stok tertentu (selain distribusi)
- `supplier_id` diisi untuk `tipe_retur = 'gudang_ke_supplier'` (supplier tujuan retur)
- **Alur Karantina:** Saat retur internal (`puskesmas_ke_gudang` / `pustu_ke_puskesmas`) diterima, `batch_stok.status` otomatis menjadi `karantina`
- **Inspeksi:** Admin melakukan inspeksi via tabel `inspeksi_retur` untuk menentukan nasib obat (layak/musnah)
- **Retur ke Supplier** (`gudang_ke_supplier`) tidak melalui karantina/inspeksi — obat langsung keluar dari sistem

---

### 26. `detail_retur_obat`

Detail item obat dalam setiap retur. Mendukung retur partial (sebagian).

| Kolom            | Tipe          | Nullable | Keterangan                              |
| ---------------- | ------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)   |          | Primary key                             |
| retur_id         | BIGINT (FK)   |          | FK ke `retur_obat.id`                   |
| obat_id          | BIGINT (FK)   |          | FK ke `obat.id`                         |
| batch_id         | BIGINT (FK)   | ✓        | FK ke `batch_stok.id` (batch yang diretur) |
| jumlah_retur     | INT           |          | Jumlah yang diretur (bisa partial)      |
| bukti_foto       | VARCHAR       | ✓        | Path ke foto bukti retur (kemasan rusak, expired, dll) |
| catatan          | TEXT          | ✓        | Catatan per item                        |

---

### 27. `inspeksi_retur`

Pencatatan hasil inspeksi obat retur yang masuk karantina.

| Kolom                | Tipe              | Nullable | Keterangan                                          |
| -------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                   | BIGINT (PK)       |          | Primary key                                         |
| retur_id             | BIGINT (FK)       |          | FK ke `retur_obat.id`                               |
| detail_retur_id      | BIGINT (FK)       |          | FK ke `detail_retur_obat.id`                        |
| batch_id             | BIGINT (FK)       |          | FK ke `batch_stok.id` (batch yang diinspeksi)       |
| hasil_inspeksi       | ENUM              |          | `layak`, `tidak_layak`, `perlu_tindakan_lanjut`     |
| tindakan             | ENUM              |          | `didistribusi_kembali`, `dimusnahkan`, `dikembalikan_ke_supplier` |
| catatan_inspeksi     | TEXT              | ✓        | Catatan hasil inspeksi                              |
| inspected_by         | BIGINT (FK)       |          | FK ke `users.id` (user yang inspeksi)               |
| tanggal_inspeksi     | DATE              |          | Tanggal inspeksi dilakukan                          |
| created_at           | TIMESTAMP         | ✓        |                                                     |
| updated_at           | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- **Layak** → `batch_stok.status` diubah ke `tersedia` → bisa didistribusi ulang
- **Tidak layak** → `batch_stok.status` diubah ke `dimusnahkan` → masuk riwayat pemusnahan
- **Perlu tindakan lanjut** → `batch_stok.status` tetap `karantina`

---

### 28. `model_prediksi`

Menyimpan model ANN (Jaringan Saraf Tiruan, PHP murni) yang sudah di-train untuk setiap kombinasi faskes + obat.

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| fasilitas_id           | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id`                      |
| obat_id                | BIGINT (FK)       |          | FK ke `obat.id`                                     |
| model_data             | LONGTEXT          |          | Bobot ANN terserialisasi (JSON)                     |
| model_path             | VARCHAR           | ✓        | Path file model (`ai-models/{fid}_{oid}.json`)      |
| akurasi_r2             | DECIMAL(5,4)      | ✓        | R² score model (0-1, semakin tinggi semakin baik)   |
| mae                    | DECIMAL(10,2)     | ✓        | Mean Absolute Error                                 |
| mape                   | DECIMAL(5,2)      | ✓        | Mean Absolute Percentage Error (%)                  |
| tanggal_training       | DATE              |          | Tanggal terakhir model di-train                     |
| data_training_count    | INT               |          | Jumlah bulan berbeda dengan pemakaian > 0           |
| fitur_digunakan        | JSON              | ✓        | Daftar fitur yang dipakai model (9 fitur)           |
| status                 | ENUM              |          | `aktif`, `kadaluarsa`, `gagal`, `data_belum_cukup`  |
| error_message          | TEXT              | ✓        | Pesan error bila training `gagal`                   |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `fasilitas_id`, `obat_id` (kombinasi unik)
- `status = 'data_belum_cukup'` jika bulan berisi data < 6 → fallback ke moving average
- Model di-retrain otomatis via cron mingguan (`ai:train-models`)
- `model_data` + file `model_path` berisi bobot ANN MLP 9-12-8-1 (lihat `docs/Prediksi AI.md` §4.2)

---

### 29. `prediksi_kebutuhan`

Menyimpan hasil prediksi AI untuk kebutuhan obat mendatang, termasuk confidence interval.

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| fasilitas_id           | BIGINT (FK)       |          | FK ke `fasilitas_kesehatan.id`                      |
| obat_id                | BIGINT (FK)       |          | FK ke `obat.id`                                     |
| model_id               | BIGINT (FK)       | ✓        | FK ke `model_prediksi.id` (model yang dipakai)      |
| periode_bulan          | INT               |          | Bulan prediksi (1-12)                               |
| periode_tahun          | INT               |          | Tahun prediksi                                      |
| jumlah_prediksi        | INT               |          | Jumlah kebutuhan yang diprediksi                    |
| confidence_lower       | INT               | ✓        | Batas bawah confidence interval 95%                 |
| confidence_upper       | INT               | ✓        | Batas atas confidence interval 95%                  |
| metode                 | ENUM              |          | `ann_php`, `moving_average`, `manual` (+ legacy `ai_gradient_boost`, `ai_random_forest` untuk data lama) |
| dibuat_oleh            | BIGINT (FK)       | ✓        | FK ke `users.id`. NULL jika otomatis dari AI        |
| catatan                | TEXT              | ✓        | Catatan tambahan                                    |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `fasilitas_id`, `obat_id`, `periode_bulan`, `periode_tahun` (kombinasi unik)
- Confidence interval 95%: "kebutuhan sebenarnya kemungkinan antara `confidence_lower` - `confidence_upper`"
- `metode = 'moving_average'` jika data historis < 6 bulan (fallback)
- Prediksi otomatis dibuat setiap minggu via cron

---

### 30. `import_data_historis`

Tracking proses import data historis dari file Excel LPLPO/RKO lama.

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| nama_file              | VARCHAR           |          | Nama file yang diimport                             |
| tipe_import            | ENUM              |          | `lplpo`, `rko`, `pemakaian`                         |
| status                 | ENUM              |          | `pending`, `proses`, `selesai`, `gagal`             |
| total_baris            | INT               |          | Jumlah baris di file                                |
| baris_berhasil         | INT               |          | Jumlah baris yang berhasil diimport                 |
| baris_gagal            | INT               |          | Jumlah baris yang gagal                             |
| pesan_error            | TEXT              | ✓        | Error message jika ada                              |
| diimport_oleh          | BIGINT (FK)       |          | FK ke `users.id`                                    |
| tanggal_import         | DATE              |          | Tanggal import dilakukan                            |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Digunakan untuk import data Excel LPLPO/RKO lama ke `riwayat_stok` dan `pemakaian_obat`
- Setelah import selesai, data bisa langsung dipakai untuk training AI

---

### 31. `pengaturan_laporan`

Menyimpan pengaturan untuk generate laporan (kop surat, tanda tangan, identitas petugas). Satu tabel untuk setting global (Dinas) dan per-faskes.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| fasilitas_id     | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. **NULL = setting global Dinas** |
| grup             | ENUM              |          | `kop_surat`, `tanda_tangan`, `identitas_laporan`, `default_laporan`, `pdf`, `format_nomor`, `rko` |
| key              | VARCHAR           |          | Nama pengaturan (contoh: `nama_dinas`, `logo_path`) |
| value            | TEXT              |          | Nilai pengaturan                                    |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `fasilitas_id`, `grup`, `key` (kombinasi unik)
- `fasilitas_id = NULL` → setting global untuk Dinas Kesehatan
- `fasilitas_id ≠ NULL` → setting per faskes (Puskesmas/Pustu)
- **Fallback logic:** Jika setting per-faskes tidak ada, gunakan setting global (`NULL`)

**Contoh data:**

| fasilitas_id | grup              | key                | value                             |
| ------------ | ----------------- | ------------------ | --------------------------------- |
| `NULL`         | `kop_surat`         | `nama_dinas`         | `Dinas Kesehatan Kabupaten Example` |
| `NULL`         | `kop_surat`         | `alamat`             | `Jl. Kesehatan No. 1`               |
| `NULL`         | `kop_surat`         | `telepon`            | `(021) 1234567`                     |
| `NULL`         | `kop_surat`         | `logo_path`          | `storage/logos/logo_dinas.png`      |
| `NULL`         | `tanda_tangan`      | `nama_kepala_dinas`  | `dr. Ahmad, M.Kes`                  |
| `NULL`         | `tanda_tangan`      | `nip_kepala_dinas`   | `19700101 199503 1 001`             |
| `NULL`         | `tanda_tangan`      | `ttd_path`           | `storage/signatures/ttd_dinas.png`  |
| `NULL`         | `identitas_laporan` | `nama_petugas_tetap` | `Siti Aminah, S.Farm`               |
| `NULL`         | `identitas_laporan` | `nip_petugas_tetap`  | `19900101 201503 2 001`             |
| `NULL`         | `identitas_laporan` | `jabatan_petugas`    | `Petugas Farmasi`                   |
| **`1` (PKM A)**    | `kop_surat`         | `nama_faskes`        | `Puskesmas Example A`               |
| **`1` (PKM A)**    | `kop_surat`         | `alamat`             | `Jl. Merdeka No. 5`                 |
| **`1` (PKM A)**    | `kop_surat`         | `telepon`            | `(021) 7654321`                     |
| **`1` (PKM A)**    | `kop_surat`         | `logo_path`          | `storage/logos/logo_pkm_a.png`      |
| **`1` (PKM A)**    | `tanda_tangan`      | `nama_kepala_faskes` | `dr. Budi, Sp.PD`                   |
| **`1` (PKM A)**    | `tanda_tangan`      | `nip_kepala_faskes`  | `19800101 200503 1 002`             |
| **`1` (PKM A)**    | `tanda_tangan`      | `ttd_path`           | `storage/signatures/ttd_pkm_a.png`  |
| **`2` (PST X)**    | `kop_surat`         | `nama_faskes`        | `Pustu Example X`                   |
| **`2` (PST X)**    | `tanda_tangan`      | `nama_kepala_faskes` | `Ns. Siti`                          |
| **`2` (PST X)**    | `tanda_tangan`      | `nip_kepala_faskes`  | `19900101 201503 2 003`             |

---

### 32. `avatar_presets`

Koleksi avatar preset yang bisa dipilih pengguna sebagai alternatif upload atau initials.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| nama             | VARCHAR           |          | Nama avatar (contoh: `Kucing Lucu`, `Dokter`)       |
| file_path        | VARCHAR           |          | Path ke file SVG/PNG di `public/storage/presets/`   |
| kategori         | ENUM              |          | `hewan`, `profesi`, `abstrak`, `emoji`, `alam`      |
| is_active        | BOOLEAN           |          | Default `true`. `false` untuk menonaktifkan         |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Total 30-50 preset (5-10 per kategori)
- Admin Utama bisa manage (aktif/nonaktif) preset
- File disimpan di `public/storage/presets/`

---

### 33. `user_preferences`

Menyimpan preferensi dan pengaturan personal setiap pengguna (avatar, tema, layout, notifikasi). Satu baris per user (1:1 dengan `users`).

| Kolom                | Tipe              | Nullable | Keterangan                                          |
| -------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                   | BIGINT (PK)       |          | Primary key                                         |
| user_id              | BIGINT (FK)       |          | FK ke `users.id` (unique)                           |
| avatar_type          | ENUM              |          | `upload`, `preset`, `initials`                      |
| avatar_path          | VARCHAR           | ✓        | Path ke file avatar (upload/preset). NULL jika initials |
| tema_warna           | ENUM              |          | `light`, `dark`, `auto`                             |
| posisi_navbar        | ENUM              |          | `sidebar`, `topbar` (bawaan Filament)               |
| sidebar_collapsed    | BOOLEAN           |          | Default `true`                                      |
| bahasa               | ENUM              |          | `id`, `en`                                          |
| items_per_halaman    | INT               |          | Default `10` (opsi: 10, 25, 50)                     |
| notifikasi_email     | BOOLEAN           |          | Default `true`                                      |
| notifikasi_browser   | BOOLEAN           |          | Default `true`                                      |
| created_at           | TIMESTAMP         | ✓        |                                                     |
| updated_at           | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `user_id` (1:1 dengan `users`)
- **Default saat user dibuat:** `avatar_type = 'initials'`, `tema_warna = 'auto'`, `posisi_navbar = 'sidebar'`, dll
- **Reset ke Default:** Tombol di Settings page untuk mengembalikan semua ke nilai default
- **Filament Avatar Override:**
  ```php
  class User extends Authenticatable
  {
      public function preferences(): HasOne
      {
          return $this->hasOne(UserPreference::class);
      }

      public function getFilamentAvatarUrl(): ?string
      {
          $prefs = $this->preferences;
          if ($prefs && $prefs->avatar_type !== 'initials') {
              return asset($prefs->avatar_path);
          }
          return null; // Filament fallback ke initials otomatis
      }
  }
  ```

**Alur Avatar:**
1. User pertama login → `avatar_type = 'initials'` → Filament render inisial nama
2. User buka Settings → pilih avatar:
   - **Upload** → `avatar_type = 'upload'`, `avatar_path = 'storage/avatars/user_1.png'` (max 1MB, resize 256x256px)
   - **Preset** → `avatar_type = 'preset'`, `avatar_path = 'storage/presets/cat_01.svg'`
   - **Initials** → `avatar_type = 'initials'`, `avatar_path = NULL`
3. Sistem render:
   - Jika `'upload'` atau `'preset'` → `<img src="{{ avatar_path }}">`
   - Jika `'initials'` → fallback Filament initials (lingkaran dengan inisial nama)

---

### 34. `neraca_tahunan`

Laporan neraca tahunan obat per fasilitas kesehatan. Menyimpan ringkasan stok awal, total masuk, total keluar, stok akhir, dan nilai stok per tahun.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| nomor_neraca     | VARCHAR(100)      |          | Nomor unik neraca (contoh: `NRC/2026/001`)          |
| fasilitas_id     | BIGINT (FK)       | ✓        | FK ke `fasilitas_kesehatan.id`. NULL = gudang       |
| tahun            | INT               |          | Tahun neraca                                        |
| status           | ENUM              |          | `draft`, `selesai` (default `draft`)                |
| catatan          | TEXT              | ✓        | Catatan neraca                                      |
| dibuat_oleh      | BIGINT (FK)       |          | FK ke `users.id`                                    |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- Unique constraint: `nomor_neraca`
- Index: `fasilitas_id`, `tahun`

---

### 35. `detail_neraca_tahunan`

Detail item obat dalam neraca tahunan.

| Kolom            | Tipe              | Nullable | Keterangan                                          |
| ---------------- | ----------------- | -------- | --------------------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                                         |
| neraca_id        | BIGINT (FK)       |          | FK ke `neraca_tahunan.id` (cascade)                  |
| obat_id          | BIGINT (FK)       |          | FK ke `obat.id` (cascade)                           |
| stok_awal        | INT               |          | Stok awal tahun (default `0`)                        |
| total_masuk      | INT               |          | Total obat masuk selama tahun (default `0`)          |
| total_keluar     | INT               |          | Total obat keluar selama tahun (default `0`)         |
| stok_akhir       | INT               |          | Stok akhir tahun (default `0`)                      |
| stok_optimum     | INT               | ✓        | Stok optimum yang disarankan                        |
| permintaan       | INT               | ✓        | Jumlah permintaan                                   |
| harga_satuan     | DECIMAL(12,2)     | ✓        | Harga satuan obat                                   |
| nilai_stok       | DECIMAL(14,2)     | ✓        | stok_akhir × harga_satuan (nilai stok akhir)        |
| keterangan       | TEXT              | ✓        | Keterangan per item                                 |
| created_at       | TIMESTAMP         | ✓        |                                                     |
| updated_at       | TIMESTAMP         | ✓        |                                                     |

**Catatan:**
- `nilai_stok` = `stok_akhir × harga_satuan` — total nilai stok per obat
- Data di-generate otomatis dari `riwayat_stok` dan `batch_stok` per tahun

---

### 36. `exports`

Tabel tracking export Filament (CSV/Excel export actions). Dibuat otomatis oleh Filament v5.

| Kolom            | Tipe              | Nullable | Keterangan                              |
| ---------------- | ----------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                             |
| completed_at     | TIMESTAMP         | ✓        | Waktu export selesai                   |
| file_disk        | VARCHAR           |          | Disk storage (contoh: `local`)          |
| file_name        | VARCHAR           | ✓        | Nama file export                        |
| exporter         | VARCHAR           |          | Class exporter FQCN                     |
| processed_rows   | INT UNSIGNED      |          | Jumlah baris diproses (default `0`)     |
| total_rows       | INT UNSIGNED      |          | Total baris                             |
| successful_rows  | INT UNSIGNED      |          | Baris berhasil (default `0`)            |
| user_id          | BIGINT (FK)       |          | FK ke `users.id`                        |
| created_at       | TIMESTAMP         | ✓        |                                         |
| updated_at       | TIMESTAMP         | ✓        |                                         |

---

### 37. `imports`

Tabel tracking import Filament (CSV/Excel import actions). Dibuat otomatis oleh Filament v5.

| Kolom            | Tipe              | Nullable | Keterangan                              |
| ---------------- | ----------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)       |          | Primary key                             |
| completed_at     | TIMESTAMP         | ✓        | Waktu import selesai                    |
| file_name        | VARCHAR           | ✓        | Nama file import                        |
| file_path        | VARCHAR           | ✓        | Path file di storage                    |
| importer         | VARCHAR           |          | Class importer FQCN                     |
| processed_rows   | INT UNSIGNED      |          | Jumlah baris diproses (default `0`)     |
| total_rows       | INT UNSIGNED      |          | Total baris                             |
| successful_rows  | INT UNSIGNED      |          | Baris berhasil (default `0`)            |
| user_id          | BIGINT (FK)       |          | FK ke `users.id`                        |
| created_at       | TIMESTAMP         | ✓        |                                         |
| updated_at       | TIMESTAMP         | ✓        |                                         |

---

### 38. `failed_import_rows`

Tabel tracking baris gagal saat import Filament. Menyimpan data dan error per baris.

| Kolom             | Tipe          | Nullable | Keterangan                                  |
| ----------------- | ------------- | -------- | ------------------------------------------- |
| id                | BIGINT (PK)   |          | Primary key                                 |
| data              | JSON          |          | Data baris yang gagal                       |
| import_id         | BIGINT (FK)   |          | FK ke `imports.id`                           |
| validation_error  | TEXT          |          | Pesan error validasi                        |
| created_at        | TIMESTAMP     | ✓        |                                             |
| updated_at        | TIMESTAMP     | ✓        |                                             |

---

### 39. `notifications`

Tabel notifikasi Laravel (database channel). Menyimpan notifikasi yang dikirim ke user.

| Kolom            | Tipe              | Nullable | Keterangan                              |
| ---------------- | ----------------- | -------- | --------------------------------------- |
| id               | CHAR(36) (PK)     |          | UUID primary key                        |
| type             | VARCHAR           |          | Class notifikasi FQCN                   |
| notifiable_type  | VARCHAR           |          | Model tujuan (biasanya `User`)          |
| notifiable_id    | BIGINT            |          | ID model tujuan                         |
| data             | TEXT              |          | Payload notifikasi (JSON)               |
| read_at          | TIMESTAMP         | ✓        | Waktu dibaca (NULL = unread)            |
| created_at       | TIMESTAMP         | ✓        |                                         |
| updated_at       | TIMESTAMP         | ✓        |                                         |

---

## Tabel Bawaan & Package (Tidak Diubah)

Tabel-tabel berikut sudah ada dari instalasi Laravel, Spatie Permission, dan Spatie Activitylog, tidak perlu dibuat migration baru:

| Tabel                      | Sumber              | Keterangan                              |
| -------------------------- | ------------------- | --------------------------------------- |
| `users`                    | Laravel             | Data pengguna                           |
| `password_reset_tokens`    | Laravel             | Token reset password                    |
| `sessions`                 | Laravel             | Session pengguna                        |
| `cache` / `cache_locks`    | Laravel             | Cache                                   |
| `jobs` / `job_batches` / `failed_jobs` | Laravel | Queue                                   |
| `notifications`            | Laravel             | Notifikasi database channel             |
| `exports`                  | Filament v5         | Tracking export action                  |
| `imports` / `failed_import_rows` | Filament v5   | Tracking import action                  |
| `permissions`              | Spatie Permission   | Daftar permission                       |
| `roles`                    | Spatie Permission   | Daftar role                             |
| `model_has_permissions`    | Spatie Permission   | Relasi permission ke model              |
| `model_has_roles`          | Spatie Permission   | Relasi role ke model                    |
| `role_has_permissions`     | Spatie Permission   | Relasi role-permission                  |
| `activity_log`             | Spatie Activitylog  | Log aktivitas pengguna                  |

---

### `activity_log` (Spatie Activitylog v5)

Mencatat semua aktivitas pengguna secara otomatis (login, buat permintaan, setujui, ubah stok, dll). Dipasang via package `spatie/laravel-activitylog` v5.

| Kolom                  | Tipe              | Nullable | Keterangan                                          |
| ---------------------- | ----------------- | -------- | --------------------------------------------------- |
| id                     | BIGINT (PK)       |          | Primary key                                         |
| log_name               | VARCHAR           | ✓        | Kategori log (default, auth, stok, permintaan, dll) |
| description            | TEXT              |          | Deskripsi aktivitas                                 |
| subject_type           | VARCHAR           | ✓        | Model terkait (PermintaanObat, DistribusiObat, dll) |
| subject_id             | BIGINT            | ✓        | ID model terkait                                    |
| event                  | VARCHAR           | ✓        | Tipe event (created, updated, deleted, approved, dll)|
| causer_type            | VARCHAR           | ✓        | Model pelaku (biasanya `App\Models\User`)           |
| causer_id              | BIGINT            | ✓        | ID pelaku (user yang melakukan aksi)                |
| attribute_changes      | JSON              | ✓        | Perubahan atribut (old/new values)                  |
| properties             | JSON              | ✓        | Properti tambahan (IP, user agent, metadata)        |
| created_at             | TIMESTAMP         | ✓        |                                                     |
| updated_at             | TIMESTAMP         | ✓        |                                                     |

**Penggunaan di Model:**
```php
use Spatie\Activitylog\Traits\LogsActivity;

class PermintaanObat extends Model
{
    use LogsActivity;

    protected static $logName = 'permintaan';
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Permintaan obat {$eventName} oleh " . auth()->user()?->name;
    }
}
```

**Index:**
- `log_name`
- `subject_type`, `subject_id` (morph index)
- `causer_type`, `causer_id` (morph index)
- `created_at`

---

## Alur Penerimaan Stok

### Alur Penerimaan Stok ke Gudang

Semua stok yang masuk ke gudang dicatat melalui `penerimaan_stok`, apapun sumbernya.

```
1. Petugas membuat penerimaan_stok
   → Status: draft
   → Pilih tipe: pembelian / hibah / stok_awal / penyesuaian
   → Jika pembelian: pilih supplier, isi nomor PO & invoice
   → Tambahkan item (obat, batch number, expired, jumlah, harga)

2. Petugas konfirmasi penerimaan
   → Status: dikonfirmasi
   → Sistem otomatis:
      a. Membuat batch_stok untuk setiap item
      b. Mencatat riwayat_stok (tipe: masuk)
      c. Menambah stok_gudang.jumlah

3. Jika dibatalkan
   → Status: dibatalkan
   → Tidak ada perubahan stok
```

**Skenario per Tipe:**

| Tipe           | Supplier? | Harga?         | Contoh Penggunaan                           |
| -------------- | --------- | -------------- | ------------------------------------------- |
| `pembelian`    | Wajib     | Wajib          | Pembelian formal dari distributor           |
| `hibah`        | Tidak     | Opsional       | Donasi dari pemerintah/lembaga lain         |
| `stok_awal`    | Tidak     | Opsional       | Stok existing saat pertama kali sistem dipakai |
| `penyesuaian`  | Tidak     | Opsional       | Koreksi stok karena kesalahan input dulu    |
| `distribusi`   | Tidak     | Opsional       | Penerimaan stok otomatis saat faskes menerima distribusi |
| `manual`       | Tidak     | Opsional       | Pencatatan masuk stok manual tanpa dokumen resmi |

---

## Alur Distribusi

### Alur Pustu → Puskesmas

```
1. Pustu membuat permintaan_obat (tipe: pustu_ke_puskesmas)
   → status: draft → menunggu_persetujuan

2. Puskesmas (admin) menyetujui/menolak
   → disetujui: status → disetujui
   → ditolak: status → ditolak

3. Puskesmas mendistribusikan via distribusi_obat
   → status permintaan → sedang_didistribusi
   → **Split Allocation**: Jika stok satu batch tidak cukup, sistem otomatis memecah ke beberapa batch (FEFO)

4. Pustu menerima distribusi
   → status distribusi → diterima
   → status permintaan → diterima
   → batch_stok Pustu bertambah (fasilitas_id = Pustu)
   → batch_stok Puskesmas berkurang
   → stok_faskes di-update (agregat)
   → penerimaan_stok otomatis dibuat (tipe: distribusi)
   → riwayat_stok tercatat
```

### Alur Puskesmas → Dinas/Gudang

```
1. Puskesmas membuat permintaan_obat (tipe: puskesmas_ke_dinas)
   → status: draft → menunggu_persetujuan

2. Admin Dinas menyetujui/menolak
   → disetujui: status → disetujui
   → ditolak: status → ditolak

3. Admin Gudang mendistribusikan via distribusi_obat
   → status permintaan → sedang_didistribusi
   → **Split Allocation**: Jika stok satu batch tidak cukup, sistem otomatis memecah ke beberapa batch (FEFO)

4. Puskesmas menerima distribusi
   → status distribusi → diterima
   → status permintaan → diterima
   → batch_stok Puskesmas bertambah (fasilitas_id = Puskesmas)
   → batch_stok Gudang berkurang (fasilitas_id = NULL)
   → stok_gudang dan stok_faskes di-update (agregat)
   → penerimaan_stok otomatis dibuat (tipe: distribusi)
   → riwayat_stok tercatat
```

---

## Alur LPLPO

### Alur LPLPO Pustu → Puskesmas

```
1. Pustu membuat LPLPO (tipe_pengajuan: pustu_ke_puskesmas)
   → status: draft → diajukan

2. Puskesmas menyetujui/menolak LPLPO
   → disetujui: status → disetujui
   → ditolak: status → ditolak

3. Setelah disetujui, Pustu bisa membuat permintaan_obat dari LPLPO
   → permintaan_obat.lplpo_id diisi dengan ID LPLPO
   → detail_lplpo.sudah_diminta = true
   → detail_lplpo.permintaan_id diisi

4. Permintaan diproses sesuai alur distribusi Pustu → Puskesmas
```

### Alur LPLPO Puskesmas → Dinas

```
1. Puskesmas membuat LPLPO (tipe_pengajuan: puskesmas_ke_dinas)
   → status: draft → diajukan

2. Admin Dinas menyetujui/menolak LPLPO
   → disetujui: status → disetujui
   → ditolak: status → ditolak

3. Setelah disetujui, Puskesmas bisa membuat permintaan_obat dari LPLPO
   → permintaan_obat.lplpo_id diisi dengan ID LPLPO
   → detail_lplpo.sudah_diminta = true
   → detail_lplpo.permintaan_id diisi

4. Permintaan diproses sesuai alur distribusi Puskesmas → Dinas/Gudang
```

---

## Alur RKO

### Alur RKO (Perencanaan Tahunan)

```
1. Admin Dinas mengelola sumber_dana (CRUD)
   → Menambahkan sumber dana baru (APBD, BOK, dll)
   → Menentukan pagu anggaran per tahun

2. Faskes (Puskesmas/Pustu) membuat RKO
   → Memilih sumber_dana yang tersedia
   → Mengisi detail_rko (usulan kebutuhan obat)
   → total_anggaran dihitung otomatis
   → status: draft → diajukan

3. Admin Dinas menyetujui/menolak RKO
   → disetujui: status → disetujui
   → sumber_dana_penggunaan tercatat (tipe: perencanaan)
   → ditolak: status → ditolak

4. RKO yang disetujui bisa menjadi acuan permintaan_obat
   → detail_rko.lplpo_referensi_id bisa diisi jika ada LPLPO referensi
   → detail_rko.prediksi_id bisa diisi jika usulan dari AI prediction
```

---

## Alur Retur Obat

### Alur Retur Internal (Puskesmas → Gudang / Pustu → Puskesmas)

Obat kembali ke dalam sistem — perlu karantina & inspeksi untuk menentukan nasib obat.

```
1. Pengirim membuat retur_obat (status: draft → menunggu_approval)
   → Upload bukti foto di detail_retur_obat
   → Alasan: expired, rusak, kelebihan_stok, dll

2. Pihak tujuan melakukan approval + inspeksi fisik
   → Disetujui → status → disetujui
   → Ditolak → status → ditolak

3. Obat dikirim kembali → status → dalam_pengiriman

4. Obat diterima → batch_stok.status = 'karantina'
   → status → diterima

5. Admin inspeksi → isi inspeksi_retur
   → Layak → batch_stok.status = 'tersedia' → status → selesai
   → Tidak layak → batch_stok.status = 'dimusnahkan' → status → selesai
   → Perlu tindakan lanjut → batch_stok.status = 'karantina'

6. Riwayat stok tercatat otomatis
```

### Alur Retur ke Supplier (Gudang → Supplier)

Obat keluar dari sistem selamanya — tidak perlu karantina atau inspeksi.

```
1. Admin Gudang membuat retur_obat (status: draft → menunggu_approval)
   → tipe_retur = 'gudang_ke_supplier'
   → fasilitas_pengirim_id = NULL (gudang)
   → fasilitas_penerima_id = NULL (supplier eksternal)
   → Alasan: expired, rusak, kelebihan_stok, dll

2. Admin Dinas/Atasan melakukan approval
   → Disetujui → status → disetujui
   → Ditolak → status → ditolak

3. Obat dikirim ke supplier → status → dalam_pengiriman

4. Konfirmasi pengiriman selesai → status → selesai
   → batch_stok.jumlah dikurangi (stok gudang berkurang)
   → batch_stok.status diubah ke 'dimusnahkan' jika stok habis

5. Riwayat stok tercatat otomatis
```

---

## Alur AI Prediction

### Training Model (Otomatis via Cron Mingguan)

```
1. Cron job berjalan setiap Minggu jam 02:00
   → php artisan ai:train-models

2. Untuk setiap kombinasi faskes + obat:
   a. Extract data dari pemakaian_obat (6+ bulan terakhir)
   b. Build features:
      - Pemakaian 3 bulan terakhir
      - Rata-rata pemakaian 6 bulan terakhir
      - Rata-rata pemakaian 12 bulan terakhir
      - Bulan (1-12) → pola musiman
      - Tipe faskes (Puskesmas/Pustu)
      - Stok saat ini
      - Trend (naik/turun)
   c. Train ANN MLP 9-12-8-1 (PHP murni, SGD + early stopping)
   d. Hitung R², MAE, MAPE (split train/test 80/20)
   e. Simpan bobot (JSON) ke model_prediksi + file ai-models/
   f. Generate prediksi untuk 3 bulan ke depan → simpan ke prediksi_kebutuhan

3. Jika data < 6 bulan:
   → status = 'data_belum_cukup'
   → Fallback: prediksi = moving average 3 bulan terakhir
   → metode = 'moving_average'
```

### Penggunaan Prediksi (Saat Membuat RKO)

```
1. User membuat RKO → memilih faskes, tahun, sumber dana

2. Sistem auto-fill detail_rko dari prediksi_kebutuhan:
   → detail_rko.usulan = prediksi_kebutuhan.jumlah_prediksi
   → detail_rko.prediksi_id = ID prediksi
   → User bisa edit manual jika perlu

3. Confidence interval ditampilkan di UI:
   → "Prediksi AI: 500 tablet (range: 450 - 550)"
   → User tahu seberapa akurat prediksi ini
```

---

## Strategi Harga Obat

Sistem menggunakan 3 level harga tanpa tabel terpisah:

| Level                  | Kolom                    | Sumber                      | Fungsi                                      |
| ---------------------- | ------------------------ | --------------------------- | ------------------------------------------- |
| **Referensi**          | `obat.harga_satuan`        | Auto-update dari batch terbaru | Harga acuan untuk RKO dan estimasi          |
| **Aktual per Batch**   | `batch_stok.harga_beli`    | Dari `detail_penerimaan_stok.harga_satuan` | Harga beli real dari supplier |
| **Perencanaan**        | `detail_rko.harga_perkiraan` | Auto-fill dari `obat.harga_satuan`, bisa diedit | Harga untuk perencanaan anggaran RKO |

**Alur Harga:**
1. Penerimaan stok dikonfirmasi → `detail_penerimaan_stok.harga_satuan` disalin ke `batch_stok.harga_beli` → `obat.harga_satuan` diupdate otomatis dari batch terbaru
2. Buat RKO → `detail_rko.harga_perkiraan` auto-fill dari `obat.harga_satuan` → user bisa edit
3. Distribusi → `sumber_dana_penggunaan.total_biaya` dihitung dari `batch_stok.harga_beli × jumlah`

---

## Konvensi Penamaan Nomor Dokumen

| Dokumen              | Format                  | Contoh                    |
| -------------------- | ----------------------- | ------------------------- |
| Permintaan Obat      | `REQ/{tahun}/{seq}`     | `REQ/2026/001`            |
| Surat Jalan          | `SJ/{tahun}/{seq}`      | `SJ/2026/001`             |
| LPLPO                | `LPLPO/{tahun}/{bulan}/{seq}` | `LPLPO/2026/01/001`   |
| RKO                  | `RKO/{tahun}/{seq}`     | `RKO/2026/001`            |
| Opname               | `OPN/{tahun}/{seq}`     | `OPN/2026/001`            |
| Retur                | `RET/{tahun}/{seq}`     | `RET/2026/001`            |
| Penerimaan Stok      | `PO/{tahun}/{seq}`      | `PO/2026/001`             |
| Pemakaian Obat       | `{faskes}/{tahun}/{seq}` | `PKM/2026/001`          |
| Neraca Tahunan       | `NRC/{tahun}/{seq}`     | `NRC/2026/001`            |

---

### 39. `detail_neraca_sumber_dana`

Rincian neraca tahunan per sumber dana, memecah detail neraca berdasarkan sumber dana masing-masing batch.

| Kolom            | Tipe          | Nullable | Keterangan                              |
| ---------------- | ------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)   |          | Primary key                             |
| neraca_id        | BIGINT (FK)   |          | FK ke `neraca_tahunan.id` (cascade)     |
| obat_id          | BIGINT (FK)   |          | FK ke `obat.id` (cascade)              |
| sumber_dana_id   | BIGINT (FK)   | ✓        | FK ke `sumber_dana.id`                  |
| stok_awal        | INT           |          | Stok awal tahun                         |
| jumlah_masuk     | INT           |          | Total obat masuk                        |
| jumlah_keluar    | INT           |          | Total obat keluar                       |
| stok_akhir       | INT           |          | Sisa stok akhir                         |
| harga_satuan     | DECIMAL(12,2) | ✓        | Harga satuan                            |
| nilai_stok       | DECIMAL(14,2) | ✓        | nilai_stok × harga_satuan               |
| created_at       | TIMESTAMP     | ✓        |                                         |
| updated_at       | TIMESTAMP     | ✓        |                                         |

---

### 40. `socialite_users`

Tabel dari package `dutchcodingcompany/filament-socialite` untuk menyimpan relasi user dengan provider OAuth.

| Kolom            | Tipe          | Nullable | Keterangan                              |
| ---------------- | ------------- | -------- | --------------------------------------- |
| id               | BIGINT (PK)   |          | Primary key                             |
| user_id          | BIGINT (FK)   |          | FK ke `users.id`                        |
| provider         | VARCHAR       |          | Nama provider (contoh: `google`)        |
| provider_id      | VARCHAR       |          | ID dari provider                        |
| provider_token   | TEXT          | ✓        | Token akses                             |
| provider_refresh_token | TEXT     | ✓        | Refresh token                           |
| created_at       | TIMESTAMP     | ✓        |                                         |
| updated_at       | TIMESTAMP     | ✓        |                                         |

**Catatan:**
- Unique constraint: `user_id`, `provider`
- Unik constraint: `provider`, `provider_id`
- Package ini menyediakan alternatif login dan linking flow

---

## Index yang Disarankan

| Tabel                  | Kolom yang di-index                           |
| ---------------------- | --------------------------------------------- |
| `fasilitas_kesehatan`  | `kode_faskes` (unique), `tipe`, `status`      |
| `obat`                 | `kode_obat` (unique), `kategori`+`status` (composite) |
| `sumber_dana`          | `kode`, `tahun` (composite unique), `status`  |
| `stok_gudang`          | `obat_id` (unique)                            |
| `stok_faskes`          | `fasilitas_id`, `obat_id` (composite unique)  |
| `batch_stok`           | `obat_id`, `fasilitas_id`, `tanggal_expired`, `status`, `batch_number` |
| `permintaan_obat`      | `nomor_permintaan` (unique), `status`+`tipe_permintaan`+`tanggal_permintaan`+`lplpo_id` (composite) |
| `distribusi_obat`      | `nomor_surat_jalan` (unique), `permintaan_id`+`status` (composite) |
| `detail_distribusi_obat` | `distribusi_id`+`obat_id`+`batch_id` (composite)                |
| `riwayat_stok`         | `obat_id`, `fasilitas_id`, `tipe`, `tanggal`  |
| `pemakaian_obat`       | `nomor_pemakaian` (unique), `fasilitas_id`+`tanggal_pemakaian`+`jenis_pelayanan` (composite) |
| `detail_pemakaian_obat` | `pemakaian_id`+`obat_id` (composite), `batch_id` |
| `laporan_lplpo`        | `nomor_laporan` (unique), `fasilitas_id`+`periode_bulan`+`periode_tahun`+`jenis_pengajuan` (composite unique) |
| `laporan_rko`          | `nomor_rko` (unique), `fasilitas_id`+`periode_tahun` (composite unique) |
| `sumber_dana_penggunaan` | `sumber_dana_id`, `tipe`, `tanggal`        |
| `model_prediksi`       | `fasilitas_id`+`obat_id` (composite unique), `status`, `tanggal_training` |
| `prediksi_kebutuhan`   | `fasilitas_id`+`obat_id`+`periode_bulan`+`periode_tahun` (composite unique), `metode` |
| `import_data_historis` | `tipe_import`, `status`, `tanggal_import`    |
| `pengaturan_laporan`   | `fasilitas_id`+`grup`+`key` (composite unique) |
| `suppliers`            | `nama` (unique), `status`                            |
| `penerimaan_stok`      | `nomor_penerimaan` (unique), `supplier_id`, `fasilitas_id`, `tipe`+`status` (composite) |
| `detail_penerimaan_stok` | `penerimaan_id`, `obat_id`                       |
| `avatar_presets`       | `kategori`, `is_active`, `nama`                 |
| `user_preferences`     | `user_id` (unique), `avatar_type`, `bahasa`     |
| `opname_stok`          | `nomor_opname` (unique), `fasilitas_id`+`tanggal_opname` (composite) |
| `retur_obat`           | `nomor_retur` (unique), `status`+`tipe_retur`+`alasan`+`tanggal_retur` (composite) |
| `detail_retur_obat`    | `retur_id`+`obat_id`+`batch_id` (composite)                       |
| `inspeksi_retur`       | `retur_id`, `detail_retur_id`, `hasil_inspeksi`, `tindakan` |
| `neraca_tahunan`       | `nomor_neraca` (unique), `fasilitas_id`+`tahun` (composite) |
| `detail_neraca_tahunan` | `neraca_id`+`obat_id` (composite) |
| `activity_log`         | `log_name`, `subject_type`+`subject_id` (morph), `causer_type`+`causer_id` (morph), `created_at` |
