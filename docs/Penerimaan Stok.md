# Penerimaan Stok — Dokumentasi Fitur

## 1. Tujuan

Mengelola pencatatan **semua stok yang masuk** ke gudang dinas atau fasilitas kesehatan (puskesmas/pustu). Mendukung 6 tipe penerimaan: pembelian, hibah, stok awal, penyesuaian, distribusi, dan manual. Setiap penerimaan yang dikonfirmasi akan otomatis membuat batch stok, menambah stok agregat, dan mencatat riwayat stok.

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

### 3.1 Alur Data

```
┌──────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│  PenerimaanStok  │────▶│ DetailPenerimaanStok │────▶│  BatchStok           │
│  (Header)        │     │  (Items)             │     │  (Batch stock)       │
│  - tipe          │     │  - obat_id           │     │  - batch_number      │
│  - status        │     │  - batch_number      │     │  - tanggal_expired   │
│  - supplier_id   │     │  - jumlah            │     │  - jumlah            │
│  - sumber_dana_id│     │  - harga_satuan      │     │  - harga_beli        │
│  - distribusi_id │     │  - sub_total         │     └──────────────────────┘
└──────────────────┘     └──────────────────────┘              │
                                                                ▼
                                                       ┌──────────────────────┐
                                                       │  StokGudang /        │
                                                       │  StokFaskes          │
                                                       │  (Aggregate stock)   │
                                                       └──────────────────────┘
                                                                │
                                                                ▼
                                                       ┌──────────────────────┐
                                                       │  RiwayatStok         │
                                                       │  (Audit trail)       │
                                                       └──────────────────────┘
```

**Alur saat penerimaan dikonfirmasi:**
1. Buat `BatchStok` untuk setiap item (dengan nomor batch, tanggal expired, harga beli)
2. Update `obat.harga_satuan` dari batch terbaru
3. Tambah `StokGudang`/`StokFaskes` (agregat per obat)
4. Catat `RiwayatStok` tipe `masuk`
5. Catat `SumberDanaPenggunaan` tipe `realisasi` (jika ada sumber dana)

### 3.2 Tipe Penerimaan

| Tipe           | Supplier? | Sumber Dana? | Harga?        | Contoh Penggunaan                           |
| -------------- | --------- | ------------ | ------------- | ------------------------------------------- |
| `pembelian`    | Wajib     | Wajib        | Wajib         | Pembelian formal dari distributor           |
| `hibah`        | Tidak     | Tidak        | Opsional      | Donasi dari pemerintah/lembaga lain         |
| `stok_awal`    | Tidak     | Tidak        | Opsional      | Stok existing saat pertama kali sistem dipakai |
| `penyesuaian`  | Tidak     | Tidak        | Opsional      | Koreksi stok karena kesalahan input         |
| `distribusi`   | Tidak     | Tidak        | Opsional      | Auto-created saat faskes terima distribusi  |
| `manual`       | Tidak     | Opsional     | Opsional      | Pencatatan manual tanpa dokumen resmi       |

### 3.3 Relasi Model

| Model                    | Relasi             | Type      | Target               |
| ------------------------ | ------------------- | --------- | -------------------- |
| `PenerimaanStok`         | `supplier()`       | BelongsTo | `Supplier`           |
| `PenerimaanStok`         | `fasilitas()`      | BelongsTo | `FasilitasKesehatan` |
| `PenerimaanStok`         | `user()`           | BelongsTo | `User`               |
| `PenerimaanStok`         | `details()`        | HasMany   | `DetailPenerimaanStok` |
| `PenerimaanStok`         | `batchStok()`      | HasMany   | `BatchStok`          |
| `PenerimaanStok`         | `sumberDana()`     | BelongsTo | `SumberDana`         |
| `PenerimaanStok`         | `distribusi()`     | BelongsTo | `DistribusiObat`     |
| `DetailPenerimaanStok`   | `penerimaan()`     | BelongsTo | `PenerimaanStok`     |
| `DetailPenerimaanStok`   | `obat()`           | BelongsTo | `Obat`               |

---

## 4. Algoritma Kunci

### 4.1 Auto-generate Nomor Penerimaan

**Format default:** `{prefix}/{tahun}/{seq}` — contoh: `PO/2026/001`

Prefix ditentukan oleh `NomorFormatService` berdasarkan tipe faskes dan tanggal. Nomor di-generate otomatis via model event `creating()`.

### 4.2 Pre-fill dari Distribusi

Ketika user mengklik "Terima" dari halaman detail distribusi, form create penerimaan terisi otomatis:
- `tipe` = `distribusi`
- `distribusi_id` = ID distribusi yang diterima
- Item otomatis terisi dari `DetailDistribusiObat`
- Batch number dan expired date dari batch distribusi

### 4.3 Mutasi Stok Saat Konfirmasi (`StokService`)

| Metode                    | Tipe Penerimaan          | Efek                                           |
| ------------------------- | ------------------------ | ---------------------------------------------- |
| `prosesPenerimaan()`      | pembelian/hibah/stok_awal/penyesuaian/manual | Buat batch + tambah stok agregat |
| `prosesPenerimaanDistribusi()` | distribusi            | Buat batch + kurangi stok pengirim + tambah stok penerima |

---

## 5. Form Schema (`PenerimaanStokForm.php`)

Menggunakan **EmbeddedTable** untuk item penerimaan + modal form untuk tambah/edit item.

### Section: Informasi Utama

| Field                    | Type        | Keterangan                                         |
| ------------------------ | ----------- | -------------------------------------------------- |
| `nomor_penerimaan`       | TextInput   | Auto-generate, suffix action untuk generate ulang  |
| `tipe`                   | Select      | 6 tipe: pembelian/hibah/stok_awal/penyesuaian/distribusi/manual |
| `tanggal_penerimaan`     | DatePicker  | Default: `now()`                                   |
| `fasilitas_id`           | Hidden      | Otomatis dari `$user->fasilitas_kesehatan_id`      |
| `distribusi_id`          | Select      | Hanya visible untuk tipe `distribusi`              |
| `supplier_id`            | Select      | Hanya visible untuk tipe `pembelian`               |
| `sumber_dana_id`         | Select      | Visible untuk tipe `pembelian` dan `manual`        |
| `nomor_po`               | TextInput   | Visible untuk tipe `pembelian`                     |
| `nomor_invoice`          | TextInput   | Visible untuk tipe `pembelian`                     |

### EmbeddedTable: Item Penerimaan

Setiap item dimasukkan via modal dengan form:

| Kolom            | Tipe Komponen  | Keterangan                                |
| ---------------- | -------------- | ----------------------------------------- |
| Pilih Obat       | Select         | Semua obat aktif, searchable              |
| Batch Number     | TextInput      | Auto-generate dari `BatchNumberGenerator` |
| Tanggal Expired  | DatePicker     | Required                                  |
| Jumlah           | TextInput      | Numeric, min:1                            |
| Harga Satuan     | TextInput      | Numeric, prefix Rp, nullable              |
| Sub Total        | TextInput      | Auto-calculated (jumlah x harga), disabled |
| Keterangan       | Textarea       | Opsional                                  |

> Untuk tipe `distribusi`, item tidak bisa ditambah/diedit/dihapus manual (hanya dari distribusi).

---

## 6. Tabel & Filter (`PenerimaanStoksTable.php`)

### Kolom

| Kolom                        | Type     | Keterangan                                |
| ---------------------------- | -------- | ----------------------------------------- |
| `nomor_penerimaan`           | Text     | Searchable, sortable, copyable            |
| `tipe`                       | Badge    | Warna berbeda per tipe                    |
| `status`                     | Badge    | `gray`=draft, `success`=dikonfirmasi, `danger`=dibatalkan |
| `tanggal_penerimaan`         | Date     | Sortable                                  |
| `fasilitas.nama`             | Text     | Placeholder: "Gudang"                     |
| `supplier.nama`              | Text     | Placeholder: "-"                          |
| `sumberDana.kode`            | Badge    | Toggleable, hidden default                |
| `total_biaya`                | Money    | Format IDR                                |
| `nomor_po` / `nomor_invoice` | Text     | Toggleable, hidden default                |
| `distribusi.nomor_surat_jalan` | Text   | Toggleable, hidden default                |

### Filters

- **Tipe** — SelectFilter: pembelian/hibah/stok_awal/penyesuaian/distribusi/manual
- **Status** — SelectFilter: draft/dikonfirmasi/dibatalkan

### Actions

| Action     | Keterangan                     |
| ---------- | ------------------------------ |
| View       | Lihat detail (infolist)        |
| Edit       | Edit record (hanya jika draft) |

### Query Scope

| Role              | Scope                                    |
| ----------------- | ---------------------------------------- |
| super_admin       | Semua data                               |
| admin_dinas       | Semua data                               |
| admin_gudang      | Semua data                               |
| puskesmas/pustu   | Hanya penerimaan milik faskesnya sendiri  |

---

## 7. Pages

| Page                    | File                                      | Keterangan                                       |
| ----------------------- | ----------------------------------------- | ------------------------------------------------ |
| ListPenerimaanStoks     | `Pages/ListPenerimaanStoks.php`           | Daftar penerimaan + tombol create                |
| CreatePenerimaanStok    | `Pages/CreatePenerimaanStok.php`          | Create dengan detail table + pre-fill distribusi |
| ViewPenerimaanStok      | `Pages/ViewPenerimaanStok.php`            | Detail view (infolist)                           |
| EditPenerimaanStok      | `Pages/EditPenerimaanStok.php`            | Edit record + konfirmasi                         |

### Create Actions

| Action              | Target Status     | Keterangan                     |
| ------------------- | ----------------- | ------------------------------ |
| Simpan              | `draft`           | Simpan sebagai draft           |
| Konfirmasi          | `dikonfirmasi`    | Simpan + proses stok otomatis  |

---

## 8. Permission & Policy

### Permission

```
view_penerimaan_stok
create_penerimaan_stok
update_penerimaan_stok
delete_penerimaan_stok
```

### Assignment ke Role

| Permission                      | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ------------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_penerimaan_stok`          |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create_penerimaan_stok`        |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `update_penerimaan_stok`        |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `delete_penerimaan_stok`        |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |

### Policy Rules (`PenerimaanStokPolicy`)

| Method   | super_admin/admin_dinas/admin_gudang | puskesmas/pustu                            |
| -------- | ------------------------------------ | ------------------------------------------ |
| `viewAny`| ✅ (hasPermission)                   | ✅ (hasPermission)                         |
| `view`   | ✅ (semua)                           | ✅ (hanya milik faskes sendiri)            |
| `create` | ✅ (hasPermission)                   | ✅ (hasPermission)                         |
| `update` | ✅ (status draft)                    | ✅ (status draft + milik sendiri)          |
| `delete` | ✅ (status draft)                    | ✅ (status draft + milik sendiri)          |

---

## 9. Cara Penggunaan

### Membuat Penerimaan Baru

1. Buka **Distribusi & Permintaan** → **Penerimaan Stok**
2. Klik **"Buat Penerimaan"**
3. Pilih **Tipe Penerimaan**: Pembelian, Hibah, Stok Awal, Penyesuaian, Distribusi, atau Manual
4. Isi field yang muncul sesuai tipe (supplier, nomor PO, invoice, sumber dana)
5. Klik **"Tambah Item"** untuk menambahkan obat:
   - Pilih obat dari dropdown (searchable)
   - Batch number (auto-generate jika diaktifkan)
   - Tanggal expired
   - Jumlah dan harga satuan
6. Ulangi untuk semua item
7. Pilih aksi:
   - **"Simpan"** → status `draft` (belum mempengaruhi stok)
   - **"Konfirmasi"** → status `dikonfirmasi` (stok otomatis bertambah)

### Menerima Distribusi

1. Buka halaman **Detail Distribusi** (status harus `dalam_pengiriman` atau `diterima`)
2. Klik tombol **"Buat Penerimaan"**
3. Form terisi otomatis (tipe=distribusi, items dari distribusi)
4. Klik **"Konfirmasi"** → stok faskes bertambah, stok pengirim berkurang

### Melihat Detail

Klik **"Lihat Detail"** pada baris penerimaan untuk melihat informasi lengkap, termasuk item obat, total biaya, dan status.

### Mengedit & Menghapus

- Hanya bisa diedit jika status masih **Draft**
- Hanya bisa dihapus jika status masih **Draft**
- **Konfirmasi tidak bisa dibatalkan** — jika salah, buat penerimaan penyesuaian baru

---

## 10. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/PenerimaanStoks/Pages\CreatePenerimaanStok.php`
- `app/Filament/Resources/PenerimaanStoks/Pages\EditPenerimaanStok.php`
- `app/Filament/Resources/PenerimaanStoks/Pages\ListPenerimaanStoks.php`
- `app/Filament/Resources/PenerimaanStoks/Pages\ViewPenerimaanStok.php`
- `app/Filament/Resources/PenerimaanStoks/PenerimaanStokResource.php`
- `app/Filament/Resources/PenerimaanStoks/Schemas\PenerimaanStokForm.php`
- `app/Filament/Resources/PenerimaanStoks/Tables\PenerimaanStoksTable.php`
- `app/Filament/Resources/PenerimaanStoks/Widgets\PenerimaanStokStatsOverview.php`
- `app/Models/PenerimaanStok.php`
- `app/Policies/PenerimaanStokPolicy.php`
