# Alokasi Dana — Dokumentasi Fitur

## 1. Tujuan

Dashboard monitoring **alokasi dan realisasi anggaran** sumber dana untuk pengadaan obat. Menampilkan visualisasi data dari `sumber_dana`, `sumber_dana_penggunaan`, `penerimaan_stok`, dan `batch_stok` dalam bentuk grafik dan statistik agregat.

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ❌ (tidak ada model khusus — query dari tabel lain) |
| Migration (schema) | ❌ |
| Policy + Gate | ❌ |
| Filament Page | ✅ (custom page, bukan Resource) |
| Widgets | ✅ (8 widgets) |

## 3. Arsitektur

Alokasi Dana adalah **custom Filament Page** (bukan Resource CRUD). Data disajikan dari query ke:
- `sumber_dana` — Pagu anggaran per sumber dana
- `sumber_dana_penggunaan` — Realisasi per tipe (perencanaan/realisasi)
- `penerimaan_stok` — Penerimaan dengan biaya
- `batch_stok` — Batch dengan sumber dana

### Widgets

| Widget | Tipe | Keterangan |
| ------ | ---- | ---------- |
| AlokasiStatsOverview | Stat overview | Total pagu, realisasi, sisa anggaran |
| AlokasiPerFaskesChart | Chart | Alokasi per fasilitas kesehatan |
| AlokasiPerKategoriChart | Chart | Alokasi per kategori obat |
| AlokasiSummaryTable | Table | Rincian alokasi per sumber dana |
| DistribusiDanaChart | Chart | Distribusi dana per sumber |
| RealisasiPerTahunChart | Chart | Realisasi per tahun |
| TopObatDanaChart | Chart | Top obat dengan dana terbesar |
| TrendPenggunaanChart | Chart | Trend penggunaan dana bulanan |

### Hak Akses

| Akses | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ----- | :---------: | :----------: | :---------: | :-------: | :---: |
| View  |     ✅      |     ✅       |     ✅      |    ❌     |  ❌   |

> Hanya role Dinas (super_admin, admin_gudang, admin_dinas) yang bisa mengakses dashboard Alokasi Dana. Faskes tidak memiliki akses.

## 4. Daftar File

### Files Baru

(Tidak ada)

### Files Modifikasi

- `app/Filament/Pages/AlokasiDana.php` — Custom page
- `app/Filament/Resources/AlokasiDana/Widgets/*.php` — 8 widget files
