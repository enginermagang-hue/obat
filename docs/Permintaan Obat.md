# Permintaan Obat — Dokumentasi Fitur

## 1. Tujuan

Mengelola **permintaan obat** antar fasilitas kesehatan bertingkat (Pustu → Puskesmas → Dinas) dengan alur status yang jelas — dari pengajuan, persetujuan, distribusi, hingga penerimaan. Mendukung dua tipe permintaan sesuai hierarki fasilitas dengan otorisasi berbasis role dan scope data per faskes.

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
┌──────────┐   ┌──────────────────────┐   ┌──────────┐
│   Pustu  │──▶│   PermintaanObat     │◀──│  Dinas   │
│ (pengirim)│   │                      │   │ (tujuan) │
└────┬─────┘   │  tipe_p: pustu_ke_    │   └──────────┘
     │         │  puskesmas            │
     │         │  status: draft →      │
     │         │  menunggu_persetujuan │
     │         │  → disetujui/ditolak  │
     │         │  → sedang_didistribusi│
     │         │  → diterima           │
     │         └───────────┬───────────┘
     │                     │
     │         ┌───────────▼───────────┐
     │         │  DetailPermintaanObat │
     └────────▶│  (Repeater items)     │
               │  - jumlah_diminta     │
               │  - jumlah_disetujui   │
               │  - jumlah_dikirim     │
               │  - jumlah_diterima    │
               └───────────────────────┘
```

### 3.2 Relasi Utama

| Model             | Relasi                    | Type     |
| ----------------- | ------------------------- | -------- |
| `PermintaanObat`  | `fasilitasPengirim()`     | BelongsTo → `FasilitasKesehatan` |
| `PermintaanObat`  | `fasilitasTujuan()`       | BelongsTo → `FasilitasKesehatan` (nullable) |
| `PermintaanObat`  | `disetujuiOleh()`         | BelongsTo → `User` (nullable) |
| `PermintaanObat`  | `details()`               | HasMany → `DetailPermintaanObat` |
| `PermintaanObat`  | `distribusi()`            | HasMany → `DistribusiObat` |
| `DetailPermintaanObat` | `obat()`             | BelongsTo → `Obat` |

---

## 4. Komponen yang Dibangun

### 4.1 Foundation

| #   | Komponen | File                                              | Keterangan                     |
| --- | -------- | ------------------------------------------------- | ------------------------------ |
| 1   | Model    | `app/Models/PermintaanObat.php`                   | Model utama permintaan obat    |
| 2   | Model    | `app/Models/DetailPermintaanObat.php`             | Detail item obat per permintaan |
| 3   | Policy   | `app/Policies/PermintaanObatPolicy.php`           | Gate `permintaan_obat`          |

**Method helper pada Model:**
```php
// Auto-generate nomor permintaan
PermintaanObat::generateNomorPermintaan(): string
// Format: RQ/{tahun}/{bulan}/{seq}  →  RQ/2026/05/0001
```

### 4.2 Filament Admin Panel

| #   | Resource           | Lokasi Files                                                      | Menu         |
| --- | ------------------ | ----------------------------------------------------------------- | ------------ |
| 4   | Resource           | `app/Filament/Resources/PermintaanObats/PermintaanObatResource.php` | Inventory → Permintaan Obat |
| 5   | Form Schema        | `app/Filament/Resources/PermintaanObats/Schemas/PermintaanObatForm.php` | —            |
| 6   | Table              | `app/Filament/Resources/PermintaanObats/Tables/PermintaanObatsTable.php` | —            |
| 7   | List Page          | `app/Filament/Resources/PermintaanObats/Pages/ListPermintaanObats.php` | —            |
| 8   | Create Page        | `app/Filament/Resources/PermintaanObats/Pages/CreatePermintaanObat.php` | —            |
| 9   | Edit Page          | `app/Filament/Resources/PermintaanObats/Pages/EditPermintaanObat.php` | —            |
| 10  | Detail Page        | `app/Filament/Resources/PermintaanObats/Pages/DetailPermintaanObat.php` | —            |
| 11  | Detail View (Blade) | `resources/views/filament/pages/detail-permintaan-obat.blade.php` | Custom Blade |

#### Form Schema (`PermintaanObatForm.php`)

Menggunakan **Full Repeater (table mode)** untuk detail obat:

```
Grid (4 kolom):
  ├─ nomor_permintaan    (TextInput, auto-generate, disabled saat menunggu)
  ├─ tanggal_permintaan  (DatePicker, default: now)
  ├─ fasilitas_tujuan    (TextInput, display-only, non-dehydrated)
  └─ status             (Select: draft / menunggu_persetujuan)

Textarea: catatan (columnSpan: 2)

Repeater: details (relationship, table mode)
  ├─ obat_id             (Select, searchable, live → auto-fill satuan & kategori)
  ├─ satuan_display      (TextInput, display-only, non-dehydrated)
  ├─ kategori_display    (TextInput, display-only, non-dehydrated)
  └─ jumlah_diminta      (TextInput, numeric, min:1, default:0)
```

**Hidden fields (auto-set):**
| Field                    | Sumber                       |
| ------------------------ | ---------------------------- |
| `tipe_permintaan`        | Berdasarkan tipe faskes user  |
| `fasilitas_pengirim_id`  | `user.fasilitasKesehatan.id` |
| `fasilitas_penerima_id`  | Pustu → puskesmas_induk_id   |

#### Tabel (`PermintaanObatsTable.php`)

| Kolom                      | Type     | Keterangan                         |
| -------------------------- | -------- | ---------------------------------- |
| `nomor_permintaan`         | Text     | Searchable, sortable               |
| `tipe_permintaan`          | Badge    | info = Pustu→Puskesmas, warning = Puskesmas→Dinas |
| `fasilitasPengirim.nama`   | Text     | Searchable                         |
| `fasilitasTujuan.nama`     | Text     | Placeholder: "Dinas Kesehatan"     |
| `status`                   | Badge    | 7 status dengan warna berbeda      |
| `tanggal_permintaan`       | Date     | Sortable                           |
| `details_count`            | Count    | Jumlah item obat                   |

**Filters:**
- `tipe_permintaan` — SelectFilter
- `status` — SelectFilter (7 opsi)

**Query Scope (baris 24-34):**
- Super admin, admin_gudang, admin_dinas → lihat **semua** data
- User faskes → hanya data di mana faskes_id = faskes_pengirim ATAU faskes_tujuan

### 4.3 Action Proses Permintaan (di Tabel)

```
Tombol "Proses Permintaan" (ActionGroup)
  └─ visible: status = "menunggu_persetujuan" AND role = admin_dinas
  └─ Modal:
       ├─ Select: [Setujui / Tolak] (live)
       └─ Textarea: alasan_penolakan (visible+hanya required jika ditolak)
  └─ Action: update record + notifikasi
```

### 4.4 Custom Detail Page

Halaman detail menggunakan **full custom Blade view** (tanpa Filament Infolist):

| Section             | Tampilan                                    |
| ------------------- | ------------------------------------------- |
| **Status Hero**     | Badge status + badge tipe + nomor & tanggal |
| **Pengirim & Tujuan** | dl/dt/dd: pengirim, tujuan, disetujui oleh |
| **Waktu Penting**   | Grid 2 kolom: created_at, disetujui, ditolak, dikirim, diterima |
| **Detail Obat**     | Tabel lengkap dengan total footer           |
| **Distribusi Terkait** | Muncul hanya jika ada distribusi           |
| **Catatan**         | Muncul hanya jika `catatan` terisi           |
| **Alasan Penolakan** | Kotak merah dengan ikon X, hanya jika ditolak |

### 4.5 Alur di Create/Edit Page

**Create:**
- 2 tombol submit: **"Simpan"** (status=draft) dan **"Kirim"** (status=menunggu_persetujuan)
- `mutateFormDataBeforeCreate()` — auto-set fasilitas_pengirim_id, tipe, status

**Edit:**
- Tombol **"Kirim"** — visible saat status draft/ditolak → ubah status ke menunggu_persetujuan
- Tombol **"Batalkan Permintaan"** — visible saat menunggu_persetujuan → ubah ke draft
- `mutateFormDataBeforeSave()` — terapkan targetStatus jika ada

---

## 5. Detail Model & Relasi

### `permintaan_obat`

| Kolom                    | Tipe              | Nullable | Keterangan                                           |
| ------------------------ | ----------------- | -------- | ---------------------------------------------------- |
| id                       | BIGINT (PK)       |          | Primary key                                          |
| nomor_permintaan         | VARCHAR           |          | Format: `RQ/{tahun}/{bulan}/{seq}` (unique)           |
| fasilitas_pengirim_id    | BIGINT (FK)       |          | FK → `fasilitas_kesehatan` (yang meminta)            |
| fasilitas_tujuan_id      | BIGINT (FK)       | ✓        | FK → `fasilitas_kesehatan` — NULL = tujuan Dinas     |
| tipe_permintaan          | ENUM              |          | `pustu_ke_puskesmas`, `puskesmas_ke_dinas`           |
| lplpo_id                 | BIGINT (FK)       | ✓        | FK → `laporan_lplpo` (jika dari LPLPO)              |
| status                   | ENUM              |          | `draft`, `menunggu_persetujuan`, `disetujui`, `ditolak`, `sedang_didistribusi`, `diterima`, `dibatalkan` |
| tanggal_permintaan       | DATE              |          | Tanggal permintaan dibuat                            |
| tanggal_disetujui        | DATE              | ✓        | Tanggal disetujui                                    |
| tanggal_ditolak          | DATE              | ✓        | Tanggal ditolak                                      |
| tanggal_dikirim          | DATE              | ✓        | Tanggal dikirim                                      |
| tanggal_diterima         | DATE              | ✓        | Tanggal diterima                                     |
| disetujui_oleh           | BIGINT (FK)       | ✓        | FK → `users` (user yang menyetujui)                 |
| catatan                  | TEXT              | ✓        | Catatan tambahan                                     |
| alasan_penolakan         | TEXT              | ✓        | Alasan jika ditolak (required saat ditolak)          |
| created_at               | TIMESTAMP         | ✓        |                                                      |
| updated_at               | TIMESTAMP         | ✓        |                                                      |

**Index:** `idx_permintaan_status_tipe` → `(status, tipe_permintaan, tanggal_permintaan, lplpo_id)`

### `detail_permintaan_obat`

| Kolom             | Tipe          | Nullable | Keterangan                          |
| ----------------- | ------------- | -------- | ----------------------------------- |
| id                | BIGINT (PK)   |          | Primary key                         |
| permintaan_id     | BIGINT (FK)   |          | FK → `permintaan_obat` (cascade)    |
| obat_id           | BIGINT (FK)   |          | FK → `obat`                         |
| jumlah_diminta    | INT           |          | Jumlah yang diminta                 |
| jumlah_disetujui  | INT           | ✓        | Jumlah yang disetujui (bisa < diminta) |
| jumlah_dikirim    | INT           | ✓        | Jumlah yang dikirim                 |
| jumlah_diterima   | INT           | ✓        | Jumlah yang diterima                |
| catatan           | TEXT          | ✓        | Catatan per item                    |

---

### Permission

Semua permission `permintaan_obat` dibuat oleh RoleAndPermissionSeeder via loop:
```
view_permintaan_obat
create_permintaan_obat
update_permintaan_obat
delete_permintaan_obat
```

| Permission                        | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| --------------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_permintaan_obat`            |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create_permintaan_obat`          |     ❌      |     ❌       |     ❌      |    ✅     |  ✅   |
| `update_permintaan_obat`          |     ✅      |     ❌       |     ✅      |    ✅     |  ✅   |
| `delete_permintaan_obat`          |     ✅      |     ❌       |     ❌      |    ✅     |  ✅   |

> **Catatan:** Super Admin **tidak bisa membuat** permintaan (`create()` return false), tetapi bisa update dan delete semua. Admin Dinas bisa update (untuk approve/tolak) tapi tidak bisa create. Pustu bisa delete hanya untuk status draft.

### Policy Rules

| Method   | Super Admin              | User Faskes (pengirim)                               |
| -------- | ------------------------ | ---------------------------------------------------- |
| `viewAny` | ✅                       | ✅ (dengan scope query)                              |
| `view`   | ✅ (semua)                | ✅ (hanya data faskes sendiri)                       |
| `create` | ❌ (tidak bisa buat)      | ✅ (harus punya faskes + permission)                 |
| `update` | ✅ (semua)                | ✅ (hanya milik sendiri + status draft/menunggu/ditolak) |
| `delete` | ✅ (semua)                | ✅ (hanya milik sendiri + status draft)              |

---

## 7. Alur Status

### Pustu → Puskesmas

```
draft  ──(Kirim)──▶  menunggu_persetujuan  ──(Setujui)──▶  disetujui
  │                        │                                    │
  │                        │ (Tolak)                             │ (Distribusi)
  │                        ▼                                    ▼
  │                     ditolak                     sedang_didistribusi
  │                        │                                    │
  │                        │ (Edit & Kirim ulang)               │ (Terima)
  │                        ▼                                    ▼
  └───────────────────── draft                               diterima
```

### Puskesmas → Dinas

```
draft  ──(Kirim)──▶  menunggu_persetujuan  ──(Setujui)──▶  disetujui
  │                        │                                    │
  │                        │ (Tolak)                             │ (Distribusi dari Gudang)
  │                        ▼                                    ▼
  │                     ditolak                     sedang_didistribusi
  │                        │                                    │
  │                        │ (Edit & Kirim ulang)               │ (Terima)
  │                        ▼                                    ▼
  └───────────────────── draft                               diterima
```

### Status Lain

| Status              | Bisa diedit? | Bisa dihapus? | Keterangan                                   |
| ------------------- | :----------: | :-----------: | -------------------------------------------- |
| `draft`             |     ✅       |     ✅        | Hanya oleh pengirim                          |
| `menunggu_persetujuan` |  ✅      |     ❌        | Hanya oleh admin_dinas/super_admin           |
| `disetujui`         |     ❌       |     ❌        | Hanya super_admin                            |
| `ditolak`           |     ✅       |     ❌        | Bisa diedit & dikirim ulang                  |
| `sedang_didistribusi` |    ❌       |     ❌        |                                               |
| `diterima`          |     ❌       |     ❌        | Status final                                 |
| `dibatalkan`        |     ❌       |     ❌        | Hanya dari menunggu_persetujuan               |

---

## 8. Filtering & Scope

Query default di tabel (`modifyQueryUsing`):

```php
// Super admin, admin_gudang, admin_dinas → no filter (lihat semua)
if ($user->hasRole('super_admin') || admin_gudang || admin_dinas) {
    return $query;
}

// User faskes → scope ke data yang relevan
return $query->where(function (Builder $q) use ($user) {
    $q->where('fasilitas_tujuan_id', $user->fasilitasKesehatan->id)
      ->orWhere('fasilitas_pengirim_id', $user->fasilitasKesehatan->id);
});
```

**Tabs di List Page** (untuk user non-super_admin):
- **Semua**
- **Permintaan dari Pustu** (`tipe_permintaan = pustu_ke_puskesmas`)
- **Permintaan ke Dinas** (`tipe_permintaan = puskesmas_ke_dinas`)

---

## 9. Testing

Belum ada test spesifik untuk fitur Permintaan Obat.

```bash
# Menjalankan semua test
php artisan test --compact

# Filter test terkait (jika sudah ada)
php artisan test --compact --filter="PermintaanObat"
```

Test yang perlu dibuat:
- `PermintaanObatPolicyTest` — view, create, update, delete untuk berbagai role
- `PermintaanObatCreateTest` — create dengan Simpan vs Kirim
- `PermintaanObatTableTest` — filter, search, scope query

---

## 10. Cara Penggunaan

### Membuat Permintaan Baru

1. Buka Filament Admin → **Inventory** → **Permintaan Obat**
2. Klik **"Buat Permintaan Obat"**
3. Isi form:
   - **Nomor Permintaan** — auto-generate
   - **Tanggal Permintaan** — default hari ini
   - **Tujuan** — display-only (auto dari tipe faskes)
   - **Status** — Draft / Menunggu Persetujuan
   - **Catatan** — opsional
4. Tambah item obat via **"Tambah Obat"** → pilih obat, jumlah akan terisi
5. Pilih aksi:
   - **"Simpan"** → status `draft` (bisa diedit lagi)
   - **"Kirim"** → status `menunggu_persetujuan` (langsung dikirim ke atasan)

### Memproses Permintaan (Admin Dinas)

1. Buka daftar permintaan → filter status `menunggu_persetujuan`
2. Klik ikon aksi → **"Proses Permintaan"**
3. Pilih:
   - **Setujui** → status berubah ke `disetujui`
   - **Tolak** → isi alasan penolakan → status `ditolak`

### Melihat Detail

Klik **"Lihat"** pada baris permintaan untuk melihat halaman detail lengkap:
- Status dan informasi header
- Pengirim, tujuan, waktu-waktu penting
- Tabel detail obat dengan jumlah diminta/disetujui/dikirim/diterima
- Distribusi terkait, catatan, dan alasan penolakan (jika ada)

### Mengedit Permintaan

- **Status draft/ditolak** — bisa diedit, dikirim ulang
- **Status menunggu_persetujuan** — bisa dibatalkan (kembali ke draft)
- **Status disetujui atau setelahnya** — tidak bisa diedit

---

## 11. Daftar File (15 files)

### Files Baru

| #   | File Path                                                      |
| --- | -------------------------------------------------------------- |
| 1   | `app/Models/PermintaanObat.php`                                |
| 2   | `app/Models/DetailPermintaanObat.php`                          |
| 3   | `app/Policies/PermintaanObatPolicy.php`                        |
| 4   | `database/migrations/2026_05_21_140010_create_permintaan_obat_table.php` |
| 5   | `database/migrations/2026_05_21_140011_create_detail_permintaan_obat_table.php` |
| 6   | `database/migrations/2026_05_25_111450_add_alasan_penolakan_to_permintaan_obat_table.php` |
| 7   | `app/Filament/Resources/PermintaanObats/PermintaanObatResource.php` |
| 8   | `app/Filament/Resources/PermintaanObats/Schemas/PermintaanObatForm.php` |
| 9   | `app/Filament/Resources/PermintaanObats/Tables/PermintaanObatsTable.php` |
| 10  | `app/Filament/Resources/PermintaanObats/Pages/ListPermintaanObats.php` |
| 11  | `app/Filament/Resources/PermintaanObats/Pages/CreatePermintaanObat.php` |
| 12  | `app/Filament/Resources/PermintaanObats/Pages/EditPermintaanObat.php` |
| 13  | `app/Filament/Resources/PermintaanObats/Pages/DetailPermintaanObat.php` |
| 14  | `resources/views/filament/pages/detail-permintaan-obat.blade.php` |
| 15  | `resources/views/components/tables/obat-detail.blade.php` (shared) |

### Files Dimodifikasi

| #   | File Path                                              |
| --- | ------------------------------------------------------ |
| 1   | `database/seeders/RoleAndPermissionSeeder.php`          |



## 12. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/PermintaanObats/Pages\CreatePermintaanObat.php`
- `app/Filament/Resources/PermintaanObats/Pages\DetailPermintaanObat.php`
- `app/Filament/Resources/PermintaanObats/Pages\EditPermintaanObat.php`
- `app/Filament/Resources/PermintaanObats/Pages\ListPermintaanObats.php`
- `app/Filament/Resources/PermintaanObats/PermintaanObatResource.php`
- `app/Filament/Resources/PermintaanObats/Schemas\PermintaanObatForm.php`
- `app/Filament/Resources/PermintaanObats/Tables\PermintaanObatsTable.php`
- `app/Filament/Resources/PermintaanObats/Widgets\PermintaanObatStatsOverview.php`
- `app/Models/PermintaanObat.php`
- `app/Policies/PermintaanObatPolicy.php`
