# Sumber Dana — Dokumentasi Fitur

## 1. Tujuan

Mengelola **master data sumber dana** untuk perencanaan anggaran obat (RKO) dan tracking realisasi pembelian. Setiap sumber dana memiliki pagu anggaran per tahun dan digunakan sebagai referensi saat membuat RKO dan penerimaan stok tipe pembelian.

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

Sumber Dana digunakan di beberapa modul:
- **RKO (Perencanaan)**: Faskes memilih sumber dana saat membuat RKO → `sumber_dana_penggunaan` tipe `perencanaan`
- **Penerimaan Stok (Realisasi)**: Saat pembelian dikonfirmasi → `sumber_dana_penggunaan` tipe `realisasi`
- **Batch Stok**: Setiap batch mencatat sumber dana asal via `sumber_dana_id`

### Relasi Model

**Model:** `App\Models\SumberDana`  
**Table:** `sumber_dana`  

**Fillable Fields:**
- `kode` — Kode unik (contoh: `APBD`, `BOK`, `BOK_P2`)
- `nama` — Nama sumber dana (contoh: `Bantuan Operasional Kesehatan`)
- `tahun` — Tahun anggaran
- `total_anggaran` — Pagu anggaran (desimal 14,2)
- `status` — ENUM: `aktif`, `nonaktif`

**Relasi:**

| Method                      | Type     | Target             |
| --------------------------- | -------- | ------------------ |
| `laporanRko()`              | HasMany  | `LaporanRko`       |
| `penerimaanStok()`          | HasMany  | `PenerimaanStok`   |
| `batchStok()`               | HasMany  | `BatchStok`        |
| `sumberDanaPenggunaans()`   | HasMany  | `SumberDanaPenggunaan` |

### Hak Akses

| Permission                 | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| -------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_sumber_dana`         |     ✅      |     ❌       |     ✅      |    ❌     |  ❌   |
| `create_sumber_dana`       |     ✅      |     ❌       |     ✅      |    ❌     |  ❌   |
| `update_sumber_dana`       |     ✅      |     ❌       |     ✅      |    ❌     |  ❌   |
| `delete_sumber_dana`       |     ✅      |     ❌       |     ✅      |    ❌     |  ❌   |

> Dikelola oleh **Admin Dinas** dan **Super Admin**. Faskes tidak bisa mengelola sumber dana — hanya memilih dari yang tersedia saat membuat RKO.

### Aturan Bisnis

1. Kombinasi `kode` + `tahun` unique
2. Sumber dana nonaktif tidak muncul di form RKO/Penerimaan Stok
3. Sisa anggaran = `total_anggaran - SUM(sumber_dana_penggunaan.total_biaya)`

## 4. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/SumberDanas/Pages\ListSumberDanas.php`
- `app/Filament/Resources/SumberDanas/Schemas\SumberDanaForm.php`
- `app/Filament/Resources/SumberDanas/SumberDanaResource.php`
- `app/Filament/Resources/SumberDanas/Tables\SumberDanasTable.php`
- `app/Models/SumberDana.php`
- `app/Policies/SumberDanaPolicy.php`
