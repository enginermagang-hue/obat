# Obat — Dokumentasi Fitur

## 1. Tujuan

Master data obat yang mencakup informasi dasar (kode, nama, kategori), VEN kategori (Vital/Esensial/Non-esensial) untuk penentuan buffer stock RKO, dan harga satuan untuk perhitungan anggaran.

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

- Data obat dimasukkan via seeder (`ObatSeeder`) atau CRUD Filament
- VEN kategori ditetapkan per obat oleh `VEN_MAP` constant di seeder
- VEN kategori digunakan di perhitungan RKO untuk menentukan buffer stock:
  - **V (Vital)** = 30% buffer → obat life-saving (Epinephrine, Oksitosin, Diazepam, OAT FDC, dll)
  - **E (Esensial)** = 20% buffer → obat esensial dasar (Amoxicillin, Parasetamol, Amlodipin, dll)
  - **N (Non-Esensial)** = 10% buffer → obat penunjang (Vitamin C, Antasida, Zinc, dll)
  - **null** = 15% buffer (default fallback)

## 4. Detail Teknis

### Model & Relasi

**Model:** `App\Models\Obat`  
**Table:** `obat`  

**Fillable Fields:**  
- `kode_obat` — Kode obat unik (e-Katalog)
- `nama_obat` — Nama lengkap obat + bentuk sediaan
- `nama_generik` — Nama generik
- `kategori` — Kategori terapeutik (Antiinfeksi, Kardiovaskular, dll)
- `ven_kategori` — CHAR(1): V/E/N, digunakan untuk buffer stock RKO
- `satuan` — Satuan (Tablet, Botol, Tube, Ampul, Kapsul, Sachet)
- `kekuatan` — Kekuatan/dosis
- `bentuk_sediaan` — ENUM: tablet, kapsul, sirup, salep, injeksi, drop, inhaler, suppositoria
- `produsen` — Produsen obat
- `kemasan` — Kemasan
- `harga_satuan` — Harga per unit (desimal 12,2)
- `status` — ENUM: aktif, nonaktif
- `metode_stok` — Metode stok (FEFO/FIFO/LIFO)

### Hak Akses

[TODO: Role apa saja yang bisa mengakses]

### Aturan Bisnis

- `kode_obat` bersifat unique
- `ven_kategori` ditetapkan oleh seeder via `VEN_MAP` constant
- Obat nonaktif (`status='nonaktif'`) tidak muncul di form RKO
- Harga satuan diisi oleh seeder (random berdasarkan range per satuan) atau dari avg batch


## 5. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/Obats/Importers\ObatImporter.php`
- `app/Filament/Resources/Obats/ObatResource.php`
- `app/Filament/Resources/Obats/Pages\ListObats.php`
- `app/Filament/Resources/Obats/Schemas\ObatForm.php`
- `app/Filament/Resources/Obats/Tables\ObatsTable.php`
- `app/Models/Obat.php`
- `app/Policies/ObatPolicy.php`
