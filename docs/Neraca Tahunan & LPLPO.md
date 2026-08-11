# Neraca Tahunan & LPLPO — Dokumentasi Lengkap

## 1. Tujuan

Mengelola **laporan neraca tahunan obat** (Neraca Tahunan) dan **LPLPO (Laporan Pemakaian dan Lembar Permintaan Obat)** sesuai standar **Kementerian Kesehatan RI**. Sistem mendukung hierarki fasilitas kesehatan (Pustu → Puskesmas → Dinas) dengan alur status yang jelas dan otorisasi berbasis role.

---

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 3. Arsitektur

### 3.0 Shared Service Pattern

```
LaporanBaseService
├── hitungStokOptimum(int $totalPemakaian, int $periodeBulan): int
├── hitungPermintaan(int $totalPemakaian, int $stokAkhir, int $periodeBulan): int
├── queryRiwayatStok(...): Builder            ← abstract
└── getPeriodeLabel(...): string              ← abstract
        │
        ▼
NeracaTahunanService extends LaporanBaseService
├── implement queryRiwayatStok()  — dari riwayat_stok per tahun
├── implement getPeriodeLabel()   — "Tahun {tahun}"
└── generate(NeracaTahunan)       — loop obat → hitung → simpan detail

LaporanLplpoService extends LaporanBaseService  (belum diimplementasi)
├── implement queryRiwayatStok()  — dari pemakaian_obat per bulan
└── implement getPeriodeLabel()   — "Bulan {bulan} Tahun {tahun}"
```

### 3.1 Neraca Tahunan (Neraca Tahunan Obat)

**Tujuan:** Laporan tahunan stok obat per fasilitas kesehatan, mengikuti format LPLPO standar Kemenkes.

**Alur Data:**

```
┌─────────────────────┐     ┌─────────────────┐     ┌────────────────────┐
│     Fasilitas       │     │   NeracaTahunan │     │ DetailNeracaTahunan│
│   (Puskesmas/Pustu) │────▶│   (Tahun)       │────▶│  (per obat)        │
└─────────────────────┘     └─────────────────┘     └────────────────────┘
```

**Model:**

| Model                | Keterangan                                                                                                                        |
| :------------------- | :-------------------------------------------------------------------------------------------------------------------------------- |
| `NeracaTahunan`      | Header neraca (nomor_neraca, fasilitas_id, tahun, status, catatan, dibuat_oleh)                                                   |
| `DetailNeracaTahunan`| Detail per obat (stok_awal, total_masuk, total_keluar, stok_akhir, stok_optimum, permintaan, harga_satuan, nilai_stok, keterangan) |

**Service:** `NeracaTahunanService extends LaporanBaseService::generate()` — auto-generate detail dari `riwayat_stok` per tahun.

### 3.2 LPLPO (Laporan Pemakaian dan Lembar Permintaan Obat)

**Tujuan:** Laporan bulanan pemakaian obat per fasilitas kesehatan, menjadi dasar permintaan obat ke periode berikutnya.

**Alur Data:**

```
┌─────────────────────┐     ┌─────────────────┐     ┌────────────────────┐
│     Fasilitas       │     │   LaporanLPLPO  │     │    DetailLPLPO     │
│   (Puskesmas/Pustu) │────▶│   (Bulanan)     │────▶│  (per obat)        │
└─────────────────────┘     └─────────────────┘     └────────────────────┘
```

**Model:**

| Model          | Keterangan                                                                                                                        |
| :------------- | :------------------------------------------------------------------------------------------------------------------------------- |
| `LaporanLPLPO` | Header LPLPO (nomor_laporan, fasilitas_id, periode_bulan, periode_tahun, tipe_pengajuan, status, dibuat_oleh)                   |
| `DetailLPLPO`  | Detail per obat (stok_awal, jumlah_masuk, jumlah_keluar, sisa_stok, permintaan_selanjutnya, sudah_diminta, permintaan_id, keterangan) |

**Alur Status:** Draft → Diajukan → Disetujui / Ditolak. Saat disetujui, detail LPLPO bisa dijadikan dasar permintaan obat (via `permintaan_obat`).

---

## 4. Format Standar LPLPO (Kemenkes RI)

### 4.1 Kolom Standar

| Kolom | Nama Kolom     | Tipe Data | Keterangan                                          |
| :---: | :------------- | :-------- | :-------------------------------------------------- |
| (1)   | No Urut        | VARCHAR   | Nomor urut obat                                     |
| (2)   | Nama Obat      | VARCHAR   | Nama obat + kekuatan                                |
| (3)   | Satuan         | VARCHAR   | Bentuk sediaan (Tablet, Kapsul, Sirup, dll)         |
| (4)   | Stok Awal      | INT       | Stok awal bulan lalu (= kolom 8 bulan lalu)         |
| (5)   | Penerimaan     | INT       | Jumlah diterima bulan ini                           |
| (6)   | Persediaan     | INT       | Total ketersediaan = Stok Awal + Penerimaan         |
| (7)   | Pemakaian      | INT       | Jumlah pemakaian bulan ini                          |
| (8)   | Sisa Stok      | INT       | Sisa stok akhir = Persediaan - Pemakaian            |
| (9)   | Stok Optimum   | INT       | Stok pengaman = Pemakaian rata-rata + 20%           |
| (10)  | Permintaan     | INT       | Jumlah yang diminta untuk periode berikutnya        |
| (11)  | Keterangan     | TEXT      | Keterangan kekosongan, jenis penyakit, dll          |

### 4.2 Mapping ke Database Saat Ini

**Kolom yang sudah ada di `detail_neraca_tahunan`:**

| Kolom DB           | Kolom LPLPO       | Keterangan                    |
| :----------------- | :---------------- | :---------------------------- |
| `stok_awal`        | (4) Stok Awal     | Stok awal periode             |
| `total_masuk`      | (5) Penerimaan    | Total obat masuk              |
| `total_keluar`     | (7) Pemakaian     | Total obat keluar             |
| `stok_akhir`       | (8) Sisa Stok     | Sisa stok akhir periode       |
| `stok_optimum`     | (9) Stok Optimum  | Perhitungan stok pengaman     |
| `permintaan`       | (10) Permintaan   | Jumlah yang diminta           |
| `harga_satuan`     | —                 | Harga per satuan              |
| `nilai_stok`       | —                 | Nilai stok akhir              |
| `keterangan`       | (11) Keterangan   | Catatan kekosongan, dll       |

**Kolom yang perlu ditambahkan:** (sudah di migration terbaru)

### 4.3 Perhitungan Otomatis

| Rumus          | Formula                                       |
| :------------- | :-------------------------------------------- |
| Persediaan     | Stok Awal + Penerimaan                        |
| Sisa Stok      | Persediaan - Pemakaian                        |
| Stok Optimum   | Pemakaian Rata-rata + 20% Stok Pengaman       |
| Permintaan     | 3 × Pemakaian - Stok Akhir                   |

---

## 5. Fitur Utama

### 5.1 Neraca Tahunan

**Create Flow:**

1. User memilih **Metode Input**: Generate Otomatis atau Input Manual
2. Pilih tahun dan fasilitas

**Generate Otomatis:**
3. Sistem auto-generate detail dari `riwayat_stok` per tahun
4. Detail mencakup: stok_awal, total_masuk, total_keluar, stok_akhir, stok_optimum, permintaan, harga_satuan, nilai_stok
5. User bisa edit status, catatan, dan detail (jika perlu)
6. Setelah selesai, status bisa diubah ke `selesai`

**Input Manual:**
3. Repeater detail muncul dan bisa diisi langsung oleh user
4. User menambahkan item obat dan mengisi semua kolom secara manual
5. Tidak ada auto-generate dari service
6. User bisa edit kapan saja setelah create

**Edit Flow:**

| Aksi              | Keterangan                     |
| :---------------- | :----------------------------- |
| Generate ulang    | Hitung ulang semua detail      |
| Tandai selesai    | Status berubah ke `selesai`    |
| Kembalikan draft  | Status kembali ke `draft`      |
| Cetak laporan     | Generate PDF                   |

### 5.2 LPLPO

**Create Flow:**

1. Pilih bulan dan tahun
2. Sistem auto-generate detail dari `pemakaian_obat` bulan sebelumnya
3. Detail mencakup: stok_awal, jumlah_masuk, jumlah_keluar, sisa_stok, stok_optimum, permintaan
4. User bisa edit detail, terutama kolom permintaan
5. Setelah selesai, status bisa diubah ke `diajukan`

**Approval Flow:**

| Aksi                   | Keterangan                                                |
| :--------------------- | :-------------------------------------------------------- |
| Admin Dinas menyetujui | Status `disetujui`                                        |
| Auto-create permintaan | Detail LPLPO dijadikan dasar permintaan obat              |

---

## 6. Permission & Policy

### 6.1 Permission per Resource

| Resource       | Aksi  | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| :------------- | :---: | :---------: | :----------: | :---------: | :-------: | :---: |
| Neraca Tahunan | view  |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| Neraca Tahunan | create|      ❌     |      ❌      |     ❌      |    ✅     |  ✅   |
| Neraca Tahunan | update|      ❌     |      ❌      |     ❌      |    ✅     |  ✅   |
| Neraca Tahunan | delete|      ✅     |      ❌      |     ❌      |    ✅     |  ✅   |
| LPLPO          | view  |      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| LPLPO          | create|      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| LPLPO          | update|      ✅     |      ✅      |     ✅      |    ✅     |  ✅   |
| LPLPO          | delete|      ✅     |      ❌      |     ❌      |    ❌     |  ❌   |

### 6.2 Policy Rules

**NeracaTahunanPolicy:**

| Method  | Aturan                                    |
| :------ | :---------------------------------------- |
| `view`  | Role dengan fasilitas_id yang sesuai     |
| `create`| Role yang punya permission                |
| `update`| Hanya milik sendiri, status draft         |
| `delete`| Hanya milik sendiri, status draft         |

**LaporanLPLPOPolicy:** Sama seperti NeracaTahunan.

---

## 7. Fitur Filament

### 7.1 Form Schema (`NeracaTahunanForm.php`)

**Grid Header:**

| Field           | Tipe     | Keterangan                                |
| :-------------- | :------- | :---------------------------------------- |
| `nomor_neraca`  | TextInput| Auto-generate, disabled (non-super_admin) |
| `fasilitas_id`  | Select   | Display-only, relationship                |
| `tahun`         | TextInput| Default tahun berjalan                    |
| `status`        | Select   | draft / selesai                           |

**Repeater:**

- Detail per obat (stok_awal, total_masuk, total_keluar, stok_akhir, stok_optimum, permintaan, harga_satuan, nilai_stok, keterangan)
- Hanya ditampilkan di halaman **Edit**, atau di **Create** jika metode_input = 'manual'
- Tersembunyi di **Create** saat metode_input = 'generate' (auto-generated oleh service)
- Fields dapat diedit (tidak di-disabled) untuk fleksibilitas input manual maupun koreksi data hasil generate

### 7.2 Table (`NeracaTahunansTable.php`)

**Kolom:**

| Kolom             | Tipe   | Keterangan                       |
| :---------------- | :----- | :------------------------------- |
| `nomor_neraca`    | Text   | Searchable, sortable             |
| `fasilitas.nama`  | Text   | Searchable                       |
| `tahun`           | Text   | Sortable                         |
| `status`          | Badge  | `draft` = gray, `selesai` = success |
| `details_count`   | Text   | Jumlah item obat                 |
| `dibuatOleh.name` | Text   | Pembuat                          |
| `created_at`      | Text   | Tanggal buat                     |

**Filters:**

| Filter   | Tipe         | Keterangan              |
| :------- | :----------- | :---------------------- |
| `status` | SelectFilter | Filter status           |
| `tahun`  | SelectFilter | Filter range tahun      |

### 7.3 Pages

| Page                  | File                                | Keterangan                    |
| :-------------------- | :---------------------------------- | :---------------------------- |
| ListNeracaTahunans    | `Pages/ListNeracaTahunans.php`      | Daftar neraca tahunan         |
| CreateNeracaTahunan   | `Pages/CreateNeracaTahunan.php`     | Create dengan auto-generate   |
| EditNeracaTahunan     | `Pages/EditNeracaTahunan.php`       | Edit detail + generate ulang  |

---

## 8. Alur Status Neraca Tahunan

```
┌─────────┐       ┌──────────┐
│  draft  │──────▶│ selesai  │
└─────────┘       └──────────┘
     │
     ▼
  (hapus)
```

| Status             | Bisa diedit? | Bisa dihapus? | Keterangan              |
| :----------------- | :----------: | :-----------: | :---------------------- |
| `draft`            |      ✅      |      ✅       | Hanya oleh pembuat      |
| `selesai`          |      ❌      |      ❌       | Status final            |

---

## 9. Alur Status LPLPO

```
┌─────────┐       ┌──────────┐       ┌──────────┐
│  draft  │──────▶│ diajukan │──────▶│disetujui │
└─────────┘       └──────────┘       └──────────┘
                       │
                       ▼
                 ┌──────────┐
                 │ ditolak  │
                 └──────────┘
```

| Status      | Bisa diedit? | Bisa dihapus? | Keterangan              |
| :---------- | :----------: | :-----------: | :---------------------- |
| `draft`     |      ✅      |      ✅       | Hanya oleh pembuat      |
| `diajukan`  |      ❌      |      ❌       | Menunggu persetujuan    |
| `disetujui` |      ❌      |      ❌       | Status final            |
| `ditolak`   |      ❌      |      ❌       | Status final            |

---

## 10. Cetak Laporan PDF

**Route:** `GET /admin/neraca-tahunan/{neraca}/cetak`

**Controller:** `CetakNeracaTahunanController@__invoke`

**Alur:**

1. Halaman detail neraca → klik "Cetak"
2. Controller load neraca + relasi (`details.obat`, `fasilitas`)
3. Generate PDF via `spatie/laravel-pdf` dengan view `pdf.neraca-tahunan`
4. Render sebagai PDF A4, ditampilkan inline di browser

**Format PDF:**

| Bagian  | Konten                                          |
| :------ | :---------------------------------------------- |
| Header  | Logo, nama fasilitas, periode                   |
| Tabel   | No, Nama Obat, Satuan, Stok Awal, Penerimaan, Persediaan, Pemakaian, Sisa Stok, Stok Optimum, Permintaan, Keterangan |
| Footer  | Dicetak pada [tanggal], dibuat oleh [user]      |

---

## 11. Cara Penggunaan

### Membuat Neraca Tahunan Baru

1. Buka Filament Admin → **Laporan** → **Neraca Tahunan**
2. Klik **"Buat Neraca Tahunan"**
3. Pilih fasilitas dan tahun
4. Sistem auto-generate detail dari riwayat stok
5. Review detail (bisa edit jika perlu)
6. Pilih aksi:

| Aksi               | Keterangan                     |
| :----------------- | :----------------------------- |
| Generate Ulang     | Hitung ulang semua detail      |
| Tandai Selesai     | Status `detail_selesai`        |
| Simpan             | Status `draft`                 |
| Cetak              | Generate PDF                   |

### Membuat LPLPO Baru

1. Buka Filament Admin → **Laporan** → **LPLPO**
2. Klik **"Buat LPLPO"**
3. Pilih bulan dan tahun
4. Sistem auto-generate detail dari pemakaian bulan sebelumnya
5. Review detail (bisa edit kolom permintaan)
6. Pilih aksi:

| Aksi    | Keterangan                         |
| :------ | :--------------------------------- |
| Kirim   | Status `menunggu_approve`          |
| Simpan  | Status `draft`                     |

### Melihat Detail

Klik **"Lihat"** pada baris neraca/LPLPO untuk melihat halaman detail lengkap:

- Status dan informasi header
- Pengirim, tujuan, waktu-waktu penting
- Tabel detail per item
- Catatan, alasan penolakan (jika ada)

---

## 12. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/LaporanLplpos/Concerns\ManagesLplpoDetails.php`
- `app/Filament/Resources/LaporanLplpos/LaporanLplpoResource.php`
- `app/Filament/Resources/LaporanLplpos/Pages\CreateLaporanLplpo.php`
- `app/Filament/Resources/LaporanLplpos/Pages\EditLaporanLplpo.php`
- `app/Filament/Resources/LaporanLplpos/Pages\ListLaporanLplpos.php`
- `app/Filament/Resources/LaporanLplpos/Pages\ShowLaporanLplpo.php`
- `app/Filament/Resources/LaporanLplpos/Schemas\LaporanLplpoForm.php`
- `app/Filament/Resources/LaporanLplpos/Tables\LaporanLplposTable.php`
- `app/Models/LaporanLplpo.php`
- `app/Policies/LaporanLplpoPolicy.php`

## 13. Testing

### Test yang Perlu Dibuat

| #  | Test                            | Keterangan                                     |
| :- | :------------------------------ | :--------------------------------------------- |
| 1  | `NeracaTahunanPolicyTest`       | View, create, update, delete untuk berbagai role |
| 2  | `NeracaTahunanCreateTest`       | Create dengan auto-generate detail             |
| 3  | `NeracaTahunanGenerateTest`     | Test perhitungan stok_optimum dan permintaan   |
| 4  | `LaporanLPLPOPolicyTest`        | View, create, update, delete untuk berbagai role |
| 5  | `LaporanLPLPOCreateTest`        | Create dengan auto-generate detail             |
| 6  | `LaporanLPLPOApprovalTest`      | Test approval flow                             |

### Cara Menjalankan Test

```bash
# Menjalankan semua test
php artisan test --compact

# Filter test terkait
php artisan test --compact --filter="NeracaTahunan"
php artisan test --compact --filter="LaporanLPLPO"
```

---

## 14. Catatan

1. **Stok Optimum** — perhitungan berdasarkan pemakaian rata-rata + stok pengaman (20%). Bisa diedit manual jika perlu.
2. **Permintaan** — perhitungan otomatis 3 × pemakaian - stok akhir. Bisa diedit manual.
3. **Sumber Dana** — tidak perlu kolom hardcoded. Data pemberian per sumber sudah tercatat di `batch_stok.sumber_dana_id`. Untuk laporan per sumber, cukup query dari `riwayat_stok`/`batch_stok` dengan `GROUP BY sumber_dana_id`.
4. **Keterangan** — digunakan untuk kekosongan obat, jenis penyakit, tanggal kekosongan, dll.
5. **Auto-generate** — detail neraca/LPLPO di-generate otomatis saat create, tapi bisa diedit jika perlu.
6. **Status** — alur status mengikuti standar Kemenkes RI.

---

*Dokumentasi ini selaras dengan `.docs/Skema Database.md` dan `.docs/Permissions.md`.*
