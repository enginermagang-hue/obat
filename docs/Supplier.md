# Supplier — Dokumentasi Fitur

## 1. Tujuan

Mengelola **master data supplier/pemasok** obat. Digunakan sebagai referensi saat mencatat penerimaan stok tipe **pembelian** dan retur ke supplier (`gudang_ke_supplier`).

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 3. Detail Teknis

### Model & Relasi

**Model:** `App\Models\Supplier`  
**Table:** `suppliers`  

**Fillable Fields:**
- `nama` — Nama supplier (unique)
- `alamat` — Alamat lengkap (nullable)
- `telepon` — Nomor telepon (nullable)
- `email` — Alamat email (nullable)
- `npwp` — NPWP supplier (nullable)
- `status` — ENUM: `aktif`, `nonaktif` (default `aktif`)

**Relasi:**

| Method            | Type     | Target             |
| ----------------- | -------- | ------------------ |
| `penerimaanStok()`| HasMany  | `PenerimaanStok`   |

### Hak Akses

| Permission                | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_suppliers`          |     ✅      |     ✅       |     ❌      |    ❌     |  ❌   |
| `create_suppliers`        |     ✅      |     ✅       |     ❌      |    ❌     |  ❌   |
| `update_suppliers`        |     ✅      |     ✅       |     ❌      |    ❌     |  ❌   |
| `delete_suppliers`        |     ✅      |     ✅       |     ❌      |    ❌     |  ❌   |

> Dikelola oleh **Super Admin** dan **Admin Gudang**. Hanya mereka yang terlibat dalam pembelian.

### Aturan Bisnis

1. `nama` bersifat unique
2. Hanya supplier dengan `status = 'aktif'` yang muncul di form penerimaan stok
3. Supplier yang memiliki relasi penerimaan stok tetap bisa dihapus (tidak ada proteksi FK restrict)

## 4. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/Suppliers/Pages\ListSuppliers.php`
- `app/Filament/Resources/Suppliers/Schemas\SupplierForm.php`
- `app/Filament/Resources/Suppliers/SupplierResource.php`
- `app/Filament/Resources/Suppliers/Tables\SuppliersTable.php`
- `app/Models/Supplier.php`
- `app/Policies/SupplierPolicy.php`
