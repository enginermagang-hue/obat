# Retur Obat — Dokumentasi Fitur

## 1. Tujuan

Mengelola **retur obat** dari fasilitas kesehatan ke gudang dinas, dari pustu ke puskesmas, atau dari gudang ke supplier. Sistem mendukung tiga alur retur:

1. **Puskesmas → Gudang** (`puskesmas_ke_gudang`): Puskesmas mengembalikan obat ke Gudang Dinas
2. **Pustu → Puskesmas** (`pustu_ke_puskesmas`): Pustu mengembalikan obat ke Puskesmas induk
3. **Gudang → Supplier** (`gudang_ke_supplier`): Gudang Dinas mengembalikan obat ke Supplier

Setiap retur memiliki **nomor retur** unik, mencatat **alasan retur**, dan dapat melalui proses **inspeksi** sebelum diproses lebih lanjut.

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

### 3.1 Alur Data

```
┌──────────────────┐     ┌────────────────────┐     ┌───────────────────────┐
│  ReturObat       │────▶│  DetailReturObat   │────▶│  InspeksiRetur        │
│                  │     │                    │     │  (opsional)           │
│  nomor_retur     │     │  - obat_id         │     │  - hasil_inspeksi     │
│  tipe_retur      │     │  - batch_id        │     │  - tindakan           │
│  alasan          │     │  - jumlah_retur    │     │  - catatan_inspeksi   │
│  status (7 state)│     │  - bukti_foto      │     │  - inspected_by       │
│  distributor_id  │     └────────────────────┘     └───────────────────────┘
│  supplier_id     │
└──────────────────┘
```

### 3.2 Relasi Model

| Model              | Relasi                | Type      | Target               |
| ------------------ | --------------------- | --------- | -------------------- |
| `ReturObat`        | `distribusi()`        | BelongsTo | `DistribusiObat`     |
| `ReturObat`        | `fasilitasPengirim()` | BelongsTo | `FasilitasKesehatan` |
| `ReturObat`        | `fasilitasPenerima()` | BelongsTo | `FasilitasKesehatan` |
| `ReturObat`        | `supplier()`          | BelongsTo | `Supplier`           |
| `ReturObat`        | `disetujuiOleh()`     | BelongsTo | `User`               |
| `ReturObat`        | `details()`           | HasMany   | `DetailReturObat`    |
| `DetailReturObat`  | `retur()`             | BelongsTo | `ReturObat`          |
| `DetailReturObat`  | `obat()`              | BelongsTo | `Obat`               |
| `DetailReturObat`  | `batch()`             | BelongsTo | `BatchStok`          |
| `DetailReturObat`  | `inspeksi()`          | BelongsTo | `InspeksiRetur`      |
| `InspeksiRetur`    | `retur()`             | BelongsTo | `ReturObat`          |
| `InspeksiRetur`    | `detailRetur()`       | BelongsTo | `DetailReturObat`    |
| `InspeksiRetur`    | `batch()`             | BelongsTo | `BatchStok`          |
| `InspeksiRetur`    | `inspector()`         | BelongsTo | `User`               |

### 3.3 Diagram Alur End-to-End

**Alur Puskesmas → Gudang:**

```
PUSKESMAS (role: puskesmas)          GUDANG DINAS (role: admin_gudang)
  │                                        │
  │  1. Buat Retur (atau dari Distribusi)  │
  │  ─────────────────────────────────────▶│
  │  status: draft                          │
  │                                        │
  │  2. Ajukan Retur                       │
  │  status: menunggu_approval             │
  │  ─────────────────────────────────────▶│
  │                                        │  3. Admin Dinas Setujui/Tolak
  │                                        │  status: disetujui/ditolak
  │                                        │
  │                                        │  4. Admin Gudang Kirim
  │                                        │  status: dalam_pengiriman
  │                                        │
  │  5. Terima Retur                       │
  │  ◀────────────────────────────────────│
  │  status: diterima                      │
  │  (stok disesuaikan otomatis)           │
  │                                        │
  │  6. Tandai Selesai                     │
  │  status: selesai                       │
```

**Alur Gudang → Supplier:**

```
GUDANG DINAS (role: admin_gudang)    SUPPLIER
  │                                        │
  │  1. Buat Retur                         │
  │  tipe: gudang_ke_supplier              │
  │  supplier_id: [pilih supplier]         │
  │                                        │
  │  2. Ajukan Retur                       │
  │  status: menunggu_approval             │
  │                                        │
  │  3. Admin Dinas Setujui                │
  │  status: disetujui                     │
  │                                        │
  │  4. Konfirmasi Kirim                   │
  │  status: dalam_pengiriman              │
  │  ─────────────────────────────────────▶│
  │                                        │
  │  5. Supplier Terima                    │
  │  status: diterima                      │
  │  (stok gudang berkurang)               │
```

---

## 4. Algoritma Kunci

### 4.1 Penyesuaian Stok (StokService::prosesReturDiterima)

Saat retur diterima, stok disesuaikan otomatis berdasarkan tipe retur:

| Tipe Retur          | Pengirim       | Penerima            | Riwayat Stok (Pengirim) | Riwayat Stok (Penerima) |
| ------------------- | -------------- | ------------------- | ----------------------- | ----------------------- |
| `pustu_ke_puskesmas`  | `stok_faskes` -- | `stok_faskes` ++      | `keluar`                  | `masuk`                  |
| `puskesmas_ke_gudang` | `stok_faskes` -- | `stok_gudang` ++      | `keluar`                  | `masuk`                  |
| `gudang_ke_supplier`  | `stok_gudang` -- | Tidak ada perubahan | `keluar`                  | -                       |

**Logika pada batch:**
1. Kurangi jumlah batch di pengirim (berdasarkan `batch_id`)
2. Jika jumlah batch habis, ubah status menjadi `dimusnahkan`
3. Tambah stok agregat penerima (kecuali `gudang_ke_supplier`)
4. Catat riwayat stok untuk setiap perubahan

**Kode kunci:**
```php
public function prosesReturDiterima(ReturObat $retur): void
{
    foreach ($retur->details as $detail) {
        $jumlah = $detail->jumlah_retur;

        // 1. Kurangi stok pengirim
        $this->kurangiStokRetur(
            retur: $retur,
            obatId: $detail->obat_id,
            batchId: $detail->batch_id,
            jumlah: $jumlah,
            isPengirim: true,
        );

        // 2. Tambah stok penerima (kecuali gudang_ke_supplier)
        if ($retur->tipe_retur !== 'gudang_ke_supplier') {
            $this->kurangiStokRetur(
                retur: $retur,
                obatId: $detail->obat_id,
                batchId: $detail->batch_id,
                jumlah: $jumlah,
                isPengirim: false,
            );
        }
    }
}
```

### 4.2 Auto-generate Nomor Retur

**Format:** `RET/{tahun}/{nomor_urut}` — contoh: `RET/2026/001`

**Cara kerja:**
- Nomor di-generate otomatis via closure `static::creating()` pada model `ReturObat`
- Query nomor terakhir dengan prefix tahun berjalan → increment + 1

```php
static::creating(function (self $record) {
    if (blank($record->nomor_retur)) {
        $record->nomor_retur = static::generateNomorRetur();
    }
});
```

### 4.3 Scope Batch oleh Fasilitas

Filter batch stok berdasarkan **fasilitas pengirim**:

| Role User         | `fasilitasId`                            | Batch yang ditampilkan                       |
| ----------------- | ---------------------------------------- | -------------------------------------------- |
| super_admin       | dari field `fasilitas_pengirim_id`       | Batch milik faskes yg dipilih di form        |
| admin_gudang      | `null` (Dinas, tanpa faskes)             | Batch dengan `fasilitas_id IS NULL` (Gudang) |
| puskesmas         | `$user->fasilitasKesehatan->id`          | Batch milik Puskesmas tersebut              |

---

## 5. Form Schema (`ReturObatForm.php`)

Menggunakan **server-driven UI** dengan pattern `static configure(Schema): Schema`.

### Section 1: Informasi Retur

| Field                | Type        | Keterangan                                    |
| -------------------- | ----------- | --------------------------------------------- |
| `nomor_retur`        | TextInput   | Auto-generate, disabled (non-super_admin)     |
| `tipe_retur`         | Select      | Auto berdasarkan role faskes, atau manual     |
| `status`             | Select      | Default: `draft` (hidden untuk non-super_admin)|
| `distribusi_id`      | Select      | Hanya untuk faskes user (hidden untuk admin)  |

### Section 2: Fasilitas

| Field                  | Type        | Keterangan                                         |
| ---------------------- | ----------- | -------------------------------------------------- |
| `fasilitas_pengirim_id`| Select      | Auto dari user faskes, atau manual (nullable = Gudang) |
| `fasilitas_penerima_id`| Select      | Hidden untuk `puskesmas_ke_gudang` dan `gudang_ke_supplier` |
| `supplier_id`          | Select      | Hanya visible untuk `gudang_ke_supplier`             |

### Section 3: Detail Retur

| Field                | Type        | Keterangan                                    |
| -------------------- | ----------- | --------------------------------------------- |
| `alasan`             | Select      | Kedaluwarsa, Rusak, Kelebihan Stok, dll       |
| `alasan_lainnya`     | Textarea    | Visible jika alasan = `lainnya`                 |
| `tanggal_retur`      | DatePicker  | Default: `now()`                              |

**Catatan:** Field `tanggal_disetujui`, `tanggal_ditolak`, `tanggal_dikirim`, `tanggal_diterima` **tidak ditampilkan** di form karena diisi otomatis oleh action transisi status.

### Section 4: Detail Obat (Embedded Table)

| Kolom       | Type Komponen | Keterangan                                |
| ----------- | ------------- | ----------------------------------------- |
| Pilih Obat  | Select        | Semua obat aktif, `live()` → reset batch  |
| Pilih Batch | Select        | FEFO, filter by faskes pengirim           |
| Jumlah      | TextInput     | Numeric, min:1, default:1                 |
| Bukti Foto  | FileUpload    | Image upload, optional                    |
| Catatan     | Textarea      | Per item, optional                        |

---

## 6. Tabel & Filter (`ReturObatsTable.php`)

### Kolom

| Kolom                      | Type     | Keterangan                         |
| -------------------------- | -------- | ---------------------------------- |
| `nomor_retur`              | Text     | Sortable, searchable               |
| `tipe_retur`               | Badge    | `warning`=Puskesmas→Gudang, `info`=Pustu→Puskesmas, `danger`=Gudang→Supplier |
| `fasilitasPengirim.nama`   | Text     | Sortable, searchable, placeholder: Gudang |
| `fasilitasPenerima.nama`   | Text     | Sortable, searchable, placeholder: Gudang/Supplier |
| `alasan`                   | Badge    | `danger`=Expired/Rusak, `warning`=Near Exp |
| `status`                   | Badge    | `gray`=draft, `warning`=menunggu, `success`=disetujui/diterima/selesai, `danger`=ditolak |
| `tanggal_retur`            | Date     | Sortable                           |
| `details_count`            | Count    | Jumlah item obat via `counts()`    |
| `created_at`               | DateTime | Toggleable, default hidden         |

### Filters (Above Content)

- **Tipe Retur** — SelectFilter: `puskesmas_ke_gudang` / `pustu_ke_puskesmas` / `gudang_ke_supplier`
- **Status** — SelectFilter: semua status
- **Alasan** — SelectFilter: semua alasan

---

## 7. Pages

| Page                   | File                                            | Keterangan                                         |
| ---------------------- | ----------------------------------------------- | -------------------------------------------------- |
| ListReturObats         | `Pages/ListReturObats.php`                      | Daftar retur + filters                             |
| CreateReturObat        | `Pages/CreateReturObat.php`                     | Create + action: Simpan (draft) + Ajukan           |
| EditReturObat          | `Pages/EditReturObat.php`                       | Edit + action: Simpan + Ajukan + Hapus             |
| ViewReturObat          | `Pages/ViewReturObat.php`                       | Detail view + action transisi status               |

### Action di Halaman Create/Edit

**CreateReturObat:**

| Action | Target Status      | Keterangan                     |
| ------ | ------------------ | ------------------------------ |
| Simpan | `draft`              | Simpan sebagai draft           |
| Ajukan | `menunggu_approval`  | Simpan + ajukan ke admin dinas |

**EditReturObat:**

| Action | Target Status      | Visibility                     |
| ------ | ------------------ | ------------------------------ |
| Simpan | (tetap)            | Status = draft/ditolak         |
| Ajukan | `menunggu_approval`  | Status = draft/ditolak         |
| Hapus  | -                  | Status = draft saja            |

### Action di Halaman View (ViewReturObat)

| Action         | Dari → Ke Status              | Siapa                 | Stok                                                 |
| -------------- | ----------------------------- | --------------------- | ---------------------------------------------------- |
| `ajukan`         | `draft → menunggu_approval`     | Pengirim              | -                                                    |
| `setujui`        | `menunggu_approval → disetujui` | Admin Dinas           | -                                                    |
| `tolak`          | `menunggu_approval → ditolak`   | Admin Dinas           | -                                                    |
| `kirim`          | `disetujui → dalam_pengiriman`  | Admin Gudang          | -                                                    |
| `terima`         | `dalam_pengiriman → diterima`   | Penerima/Admin Gudang | **DB::transaction** + `StokService::prosesReturDiterima()` |
| `tandai_selesai` | `diterima → selesai`            | Pengirim              | -                                                    |

---

## 8. Permission & Policy

### Permission

Semua permission `retur_obat` dibuat oleh `RoleAndPermissionSeeder` via loop:

```
view_retur_obat
create_retur_obat
update_retur_obat
delete_retur_obat
```

### Assignment ke Role

| Permission                  | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| --------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_retur_obat`           |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create_retur_obat`         |     ✅      |     ✅       |     ❌      |    ✅     |  ❌   |
| `update_retur_obat`         |     ✅      |     ✅       |     ✅      |    ✅     |  ❌   |
| `delete_retur_obat`         |     ✅      |     ✅       |     ❌      |    ✅     |  ❌   |

### Policy Rules (`ReturObatPolicy`)

| Method   | super_admin | admin_gudang           | admin_dinas          | puskesmas                                    | pustu                                    |
| -------- | ----------- | ---------------------- | -------------------- | -------------------------------------------- | ---------------------------------------- |
| `viewAny`| ✅          | ✅ (hasPermission)     | ✅ (hasPermission)   | ✅ (hasPermission)                           | ✅ (hasPermission)                       |
| `view`   | ✅ (semua)  | ✅ (pengirim/penerima) | ✅ (semua)           | ✅ (pengirim/penerima = faskes)              | ✅ (pengirim = faskes)                   |
| `create` | ✅          | ✅                     | ❌                   | ✅ (punya faskes)                            | ❌                                       |
| `update` | ✅ (semua)  | ✅ (pengirim: draft/menunggu; penerima: dalam_pengiriman) | ✅ (menunggu_approval saja) | ✅ (pengirim: draft/menunggu; penerima: dalam_pengiriman) | ❌ |
| `delete` | ✅ (semua)  | ✅ (draft saja)        | ❌                   | ✅ (milik sendiri + draft)                   | ❌                                       |

---

## 9. Alur Status

```
                    ┌─────────┐
                    │  draft  │
                    └────┬────┘
                         │ (Ajukan)
                         ▼
            ┌────────────────────────┐
            │   menunggu_approval    │
            └────────┬───────────────┘
                     │
           ┌────────┴────────┐
           ▼                 ▼
    ┌──────────┐     ┌──────────┐
    │disetujui │     │ ditolak  │
    └────┬─────┘     └──────────┘
         │ (Kirim)
         ▼
┌───────────────────┐
│ dalam_pengiriman  │
└────────┬──────────┘
         │ (Terima)
         ▼
┌──────────┐
│ diterima │
└────┬─────┘
     │ (Selesai)
     ▼
┌──────────┐
│ selesai  │
└──────────┘
```

| Status             | Action         | Role yang Bisa                    | Keterangan                                  |
| ------------------ | -------------- | --------------------------------- | ------------------------------------------- |
| `draft`            | `ajukan`         | Pengirim, super_admin             | Mengajukan untuk persetujuan                |
| `menunggu_approval`| `setujui`         | admin_dinas                       | Menyetujui retur                            |
| `menunggu_approval`| `tolak`           | admin_dinas                       | Menolak retur (dengan alasan)               |
| `disetujui`        | `kirim`           | admin_gudang, super_admin         | Mengkonfirmasi pengiriman                   |
| `dalam_pengiriman` | `terima`          | Penerima, admin_gudang, super_admin | Menerima retur + penyesuaian stok        |
| `diterima`         | `tandai_selesai`  | Pengirim, super_admin             | Menandai retur selesai                      |

---

## 10. Inspeksi Retur

Inspeksi retur memungkinkan admin_gudang memeriksa kondisi obat yang diretur sebelum diproses lebih lanjut.

### Hasil Inspeksi

| Hasil                   | Keterangan                        |
| ----------------------- | --------------------------------- |
| `layak`                   | Obat dalam kondisi baik           |
| `tidak_layak`             | Obat rusak/tidak bisa digunakan   |
| `perlu_tindakan_lanjut`   | Perlu pemeriksaan lebih lanjut    |

### Tindakan

| Tindakan                    | Keterangan                        |
| --------------------------- | --------------------------------- |
| `didistribusi_kembali`        | Obat didistribusikan kembali      |
| `dimusnahkan`                 | Obat dimusnahkan                  |
| `dikembalikan_ke_supplier`    | Obat dikembalikan ke supplier     |

---

## 11. Notifikasi

| Event                      | Notifikasi ke                                                    |
| -------------------------- | ---------------------------------------------------------------- |
| Retur diajukan (`ajukan`)    | Semua `admin_dinas` + `admin_gudang`                              |
| Retur disetujui (`setujui`)  | Pengirim retur (puskesmas/pustu)                                 |
| Retur ditolak (`tolak`)      | Pengirim retur (puskesmas/pustu)                                 |
| Retur siap dikirim (`kirim`) | Penerima retur (jika ada faskes penerima)                        |
| Retur diterima (`terima`)    | Pengirim retur + admin_gudang                                    |

---

## 12. Cara Penggunaan

### Membuat Retur dari Distribusi

1. Buka halaman **Detail Distribusi** (status harus `dalam_pengiriman`)
2. Klik tombol **"Terima"**
3. Di modal, tambahkan item retur jika ada obat yang perlu diretur:
   - Pilih item obat
   - Masukkan jumlah retur
   - Pilih alasan retur
   - Tambah catatan (opsional)
4. Klik **"Terima"** — retur akan dibuat otomatis dengan status `menunggu_approval`

### Membuat Retur Manual

1. Buka **Retur Obat** → **Buat Retur**
2. Isi form:
   - **Nomor Retur** — auto-generate
   - **Tipe Retur** — pilih: Puskesmas→Gudang, Pustu→Puskesmas, atau Gudang→Supplier
   - **Distribusi Terkait** — pilih distribusi (opsional, hanya untuk faskes user)
   - **Alasan Retur** — pilih alasan
   - **Detail Obat** — tambahkan obat yang diretur
3. Klik:
   - **"Simpan"** — simpan sebagai draft
   - **"Ajukan"** — simpan + langsung ajukan ke admin dinas

### Mengajukan Retur dari Edit

1. Buka retur (status harus `draft` atau `ditolak`)
2. Klik **"Ajukan Retur"** di header action
3. Retur akan dikirim ke admin_dinas untuk persetujuan

### Menyetujui/Menolak Retur (Admin Dinas)

1. Buka detail retur (status harus `menunggu_approval`)
2. Klik **"Setujui"** atau **"Tolak"**
3. Jika menolak, masukkan alasan penolakan

### Mengirim Retur (Admin Gudang)

1. Buka detail retur (status harus `disetujui`)
2. Klik **"Kirim Retur"**
3. Konfirmasi pengiriman

### Menerima Retur

1. Buka detail retur (status harus `dalam_pengiriman`)
2. Klik **"Terima Retur"**
3. Stok akan disesuaikan otomatis:
   - Stok pengirim berkurang
   - Stok penerima bertambah (kecuali gudang_ke_supplier)

---

## 13. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/ReturObats/Pages\CreateReturObat.php`
- `app/Filament/Resources/ReturObats/Pages\EditReturObat.php`
- `app/Filament/Resources/ReturObats/Pages\ListReturObats.php`
- `app/Filament/Resources/ReturObats/Pages\ViewReturObat.php`
- `app/Filament/Resources/ReturObats/ReturObatResource.php`
- `app/Filament/Resources/ReturObats/Schemas\ReturObatForm.php`
- `app/Filament/Resources/ReturObats/Tables\ReturObatsTable.php`
- `app/Models/ReturObat.php`
- `app/Policies/ReturObatPolicy.php`
