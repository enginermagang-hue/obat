# Laporan RKO — Dokumentasi Fitur

## 1. Tujuan

RKO (Rencana Kebutuhan Obat) adalah laporan perencanaan pengadaan obat tahunan yang wajib dibuat setiap fasilitas kesehatan (Puskesmas/Pustu). Sistem menghitung kebutuhan obat berdasarkan **rumus Kemenkes** (`rata_rata_pemakaian_bulanan × 18`) dengan buffer stock berdasarkan **VEN kategori** (Vital=30%, Esensial=20%, Non-esensial=10%).

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 3. Alur / Cara Kerja

### 3.1 Alur Pembuatan RKO

```
User faskes buka Create RKO
  → Pilih periode tahun (auto-fill dari pengaturan admin)
  → Pilih metode generate:
     ├─ "Generate dari Pemakaian" → isi dari riwayat stok keluar tahun lalu
     └─ "Generate dari Prediksi" → isi dari prediksi AI/MA obat
  → Review & edit detail item (usulan, stok, dll)
  → Simpan RKO (status: draft)
  → Ajukan RKO (status: diajukan)
  → Admin dinas approve/reject
```

### 3.2 Generate dari Pemakaian

- Sumber: `LaporanRkoService::previewData()` — riwayat keluar dari `riwayat_stok` tahun sebelumnya
- `rata_rata_pemakaian_bulanan` = `sum(keluar) / 12`
- Item yang di-generate: obat yang punya riwayat pemakaian ATAU punya stok > 0
- `prediksi_id` = null (tidak terhubung ke prediksi)

### 3.3 Generate dari Prediksi AI

- Sumber: `PrediksiKebutuhan` — hasil prediksi AI (ANN) atau Moving Average
- `rata_rata_pemakaian_bulanan` = `jumlah_prediksi` (dari prediksi bulan terbaru)
- `pemakaian_tahun_sebelumnya` = `jumlah_prediksi × 12` (estimasi)
- Item yang di-generate: **semua obat aktif** (obat tanpa prediksi diisi 0, user edit manual)
- `prediksi_id` = FK ke `prediksi_kebutuhan.id`
- `keterangan` = "Prediksi: {jumlah_prediksi} ({metode}, range: {lower}–{upper})"
- Kolom "AI" (sparkles icon) menandai item dari prediksi

### 3.4 Rumus Kemenkes (per item)

```
kebutuhan_tahunan = rata_rata_pemakaian_bulanan × 18
rencana_kebutuhan = max(0, kebutuhan_tahunan - stok_akhir)
buffer_persen     = 30% (V) / 20% (E) / 10% (N) / 15% (null)
buffer_qty        = round(rencana_kebutuhan × buffer_persen / 100)
total_kebutuhan   = rencana_kebutuhan + buffer_qty
usulan            = total_kebutuhan (default, user bisa edit)
total_harga       = usulan × harga_perkiraan
```

### 3.5 ABC Kategori

Setelah semua item terisi, ABC kategori dihitung berdasarkan rata-rata pemakaian bulanan kumulatif:
- **A**: kumulatif ≤ 70%
- **B**: kumulatif 70%–90%
- **C**: kumulatif > 90%

## 4. Detail Teknis

### Model & Relasi

**Model:** `App\Models\LaporanRko`  
**Table:** `laporan_rko`  

**Fillable Fields:**  
- `nomor_rko`
- `fasilitas_id`
- `periode_tahun`
- `status`
- `tanggal_pembuatan`
- `tanggal_pengajuan`
- `tanggal_disetujui`
- `total_anggaran`
- `dibuat_oleh`
- `disetujui_oleh`
- `catatan`

**Detail Model:** `App\Models\DetailRko`  
**Table:** `detail_rko`

**Detail Fields:**
- `rko_id`, `obat_id`, `prediksi_id` (FK ke `prediksi_kebutuhan`)
- `pemakaian_tahun_sebelumnya`, `rata_rata_pemakaian_bulanan`, `stok_akhir`
- `kebutuhan_tahunan`, `rencana_kebutuhan`, `usulan`
- `buffer_stock_persen`, `buffer_stok_qty`, `total_kebutuhan`
- `ven_kategori`, `abc_kategori`
- `harga_perkiraan`, `total_harga`
- `keterangan`

### Hak Akses

| Aksi | super_admin | admin_gudang | admin_dinas | user faskes |
| ---- | :---------: | :----------: | :---------: | :---------: |
| View | ✅ semua | ✅ semua | ✅ semua | ✅ milik sendiri |
| Create | ✅ | ❌ | ❌ | ✅ (jika akses dibuka & belum ada) |
| Update | ✅ semua | ❌ | ✅ approve/reject | ✅ draft milik sendiri |
| Delete | ✅ semua | ❌ | ❌ | ✅ draft milik sendiri |

**Syarat tambahan create (role user faskes):**
1. Admin harus buka akses RKO (`pengaturan_laporan: grup=rko, key=akses_dibuka, value=1`)
2. Admin harus tentukan periode tahun (`grup=rko, key=periode_tahun`)
3. Belum ada RKO untuk faskes+tahun tersebut (1 RKO per faskes per tahun)

### Aturan Bisnis

- 1 RKO per fasilitas per tahun (unique constraint)
- Status flow: `draft` → `diajukan` → `disetujui` / `ditolak`
- VEN kategori menentukan buffer stock (V=30%, E=20%, N=10%, null=15%)
- Prediksi AI terhubung via `detail_rko.prediksi_id` (nullable FK)

## 5. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/LaporanRkos/Concerns\ManagesRkoDetails.php`
- `app/Filament/Resources/LaporanRkos/LaporanRkoResource.php`
- `app/Filament/Resources/LaporanRkos/Pages\CreateLaporanRko.php`
- `app/Filament/Resources/LaporanRkos/Pages\EditLaporanRko.php`
- `app/Filament/Resources/LaporanRkos/Pages\ListLaporanRkos.php`
- `app/Filament/Resources/LaporanRkos/Pages\ViewLaporanRko.php`
- `app/Filament/Resources/LaporanRkos/Schemas\LaporanRkoForm.php`
- `app/Filament/Resources/LaporanRkos/Tables\LaporanRkosTable.php`
- `app/Models/LaporanRko.php`
- `app/Policies/LaporanRkoPolicy.php`
