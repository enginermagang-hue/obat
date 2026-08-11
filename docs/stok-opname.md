# Dokumentasi Fitur Stok Opname

## 1. Gambaran Umum

Fitur **Stok Opname** (Stock Opname / Stocktaking) adalah modul untuk melakukan pencatatan dan penyesuaian stok obat di fasilitas kesehatan. Modul ini mendukung tiga jenis opname:

| Tipe | Keterangan |
|------|------------|
| **Penyesuaian** (`penyesuaian`) | Mencocokkan stok fisik dengan stok sistem. Selisih (`stok_fisik - stok_sistem`) akan otomatis menyesuaikan stok agregat. |
| **Stok Awal** (`stok_awal`) | Pencatatan stok perdana saat pertama kali sistem digunakan. Selisih selalu 0. |
| **Stok Baru** (`stok_baru`) | Penambahan stok baru ke dalam sistem (misalnya obat baru masuk). Selisih selalu 0. |

### Alur Kerja Umum

```
┌──────────────────────────────────────────────────────────────┐
│                    STOK OPNAME WORKFLOW                       │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐    ┌──────────────┐    ┌──────────┐           │
│  │  DAFTAR  │───→│   BUAT       │───→│  LIHAT   │           │
│  │  Opname  │    │  (3 Tab)     │    │  Detail  │           │
│  └──────────┘    └──────┬───────┘    └──────────┘           │
│         ↑               │                        ↑          │
│         │     Simpan Draft             Selesai    │          │
│         │               ↓                        │          │
│         │         ┌──────────┐                    │          │
│         │         │  EDIT    │────────────────────┘          │
│         │         │ (Draft)  │  Edit lalu Selesai           │
│         │         └──────────┘                               │
│         │                                                    │
│         └──────── Selesai = tidak bisa diedit ──────────────┘│
│                                                              │
│  SAAT STATUS "SELESAI":                                      │
│  1. Selisih ≠ 0 → stok agregat disesuaikan                  │
│  2. Jika selisih > 0 & ada batch → BatchStok dibuat/ditambah │
│  3. RiwayatStok dicatat (tipe = 'opname')                    │
│  4. Batch aggregates direkalkulasi                           │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 2. Struktur Database

### Tabel `opname_stok`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint, PK, auto-increment | |
| `nomor_opname` | varchar(255), UNIQUE | Nomor dokumen (auto-generate) |
| `tipe` | enum(`penyesuaian`,`stok_awal`,`stok_baru`) | Jenis opname |
| `fasilitas_id` | bigint, FK → `fasilitas_kesehatan.id`, nullable | Fasilitas tempat opname. `null` = gudang/dinas |
| `tanggal_opname` | date | Tanggal opname |
| `status` | enum(`draft`,`selesai`) | Status workflow |
| `user_id` | bigint, FK → `users.id` | Petugas yang melakukan opname |
| `catatan` | text, nullable | Catatan tambahan |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Index: `[fasilitas_id, tanggal_opname]`

### Tabel `detail_opname_stok`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint, PK | |
| `opname_id` | bigint, FK → `opname_stok.id` (cascade) | Induk opname |
| `obat_id` | bigint, FK → `obat.id` (cascade) | Obat yang diopname |
| `batch_id` | bigint, FK → `batch_stok.id`, nullable | Batch terkait (untuk penyesuaian) |
| `stok_sistem` | integer | Stok menurut sistem |
| `stok_fisik` | integer | Stok hasil hitungan fisik |
| `selisih` | integer | `stok_fisik - stok_sistem` |
| `batch_number` | varchar(100), nullable | Nomor batch (untuk stok awal/baru) |
| `tanggal_expired` | date, nullable | Tanggal kedaluwarsa |
| `keterangan` | text, nullable | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Relasi

```
OpnameStok
  ├── belongsTo → FasilitasKesehatan (fasilitas_id)
  ├── belongsTo → User (user_id)
  └── hasMany → DetailOpnameStok (opname_id)

DetailOpnameStok
  ├── belongsTo → OpnameStok (opname_id)
  ├── belongsTo → Obat (obat_id)
  └── belongsTo → BatchStok (batch_id)
```

### Dampak ke Tabel Lain Saat Opname Selesai

- **StokFaskes** / **StokGudang**: Jumlah stok agregat disesuaikan sebesar `selisih`
- **BatchStok**: Jika `selisih > 0` dan ada data batch, batch baru dibuat atau jumlahnya ditambah
- **RiwayatStok**: Dicatat dengan `tipe = 'opname'`

---

## 3. Nomor Opname (Auto-generate)

Format nomor berbeda per tipe:

| Tipe | Prefix | Contoh |
|------|--------|--------|
| Penyesuaian | `OPN/{KODE_FASKES}/{YYYY}/{MM}/` | `OPN/PKM-A/2026/06/0001` |
| Stok Awal | `STK-AWAL/{KODE_FASKES}/{YYYY}/{MM}/` | `STK-AWAL/PKM-A/2026/06/0001` |
| Stok Baru | `STK-BARU/{KODE_FASKES}/{YYYY}/{MM}/` | `STK-BARU/PKM-A/2026/06/0001` |

Jika `fasilitas_id` tidak ada (gudang), kode faskes diganti dengan `GUD`.

Nomor di-generate otomatis melalui:
1. **Model Event**: `OpnameStok::creating()` — jika `nomor_opname` kosong saat create
2. **Tombol Generate**: Tersedia di form (icon refresh) untuk generate ulang
3. **Method**: `OpnameStok::generateNomorOpname($record, $tipe)`

---

## 4. Batch Number (untuk Stok Awal & Stok Baru)

Untuk tipe `stok_awal` dan `stok_baru`, nomor batch di-generate otomatis melalui `BatchNumberGenerator`.

**Format:** `{KODE_OBAT}-{YYMM}-{XXXX}`

Contoh: `PARA-2606-0001`

Rincian:
- `KODE_OBAT`: Kode obat dari tabel `obat` (di-uppercase)
- `YYMM`: Tahun dan bulan referensi (2 digit tahun + 2 digit bulan)
- `XXXX`: Nomor urut, padding 4 digit

**Konfigurasi:** Generate otomatis dapat dimatikan via `config('app.batch_number_auto_generate')`.

---

## 5. Formulir Create/Edit

### Struktur Tabs

Form menggunakan **Tabs** yang terikat pada Livewire property `tipe`. Hanya tab yang aktif yang data-nya dikirim (`dehydrated`).

#### Tab 1: Penyesuaian (Stok Existing)
- **Nomor Opname** — TextInput + tombol generate
- **Tanggal Opname** — DatePicker, default hari ini
- **Catatan** — Textarea
- **Item Opname** — Repeater dengan kolom tabel:
  - `Obat` — Select (searchable). Setelah dipilih, `stok_sistem` terisi otomatis dari `StokFaskes` / `StokGudang`
  - `Batch` — Select (opsional), filter batch tersedia untuk obat tersebut
  - `Stok Sistem` — Terisi otomatis berdasarkan stok agregat saat ini
  - `Stok Fisik` — Input manual, `minValue(0)`
  - `Selisih` — Readonly, auto-calculated: `stok_fisik - stok_sistem`
- **Logika selisih:** Selisih positif = stok bertambah (kelebihan fisik), selisih negatif = stok berkurang (kekurangan fisik)

#### Tab 2: Stok Awal
- **Nomor Opname** — Prefix `STK-AWAL/...`
- **Tanggal Opname** — DatePicker
- **Catatan** — Textarea
- **Item Opname** — Repeater:
  - `Obat` — Select (searchable)
  - `Stok Fisik` — Input jumlah stok awal
  - `Nomor Batch` — Auto-generate dari `BatchNumberGenerator`
  - `Tanggal Expired` — DatePicker
- `stok_sistem` dan `selisih` di-hidden, `selisih` selalu 0

#### Tab 3: Stok Baru
- **Nomor Opname** — Prefix `STK-BARU/...`
- Struktur sama dengan **Stok Awal**
- Digunakan untuk menambahkan stok obat baru yang belum tercatat

### Tombol Actions

| Tombol | Status | Keterangan |
|--------|--------|------------|
| **Simpan (Draft)** | Tersedia di create & edit | Menyimpan sebagai draft, stok tidak terpengaruh |
| **Selesai** | Tersedia di create & edit | Menyimpan dan memproses penyesuaian stok |
| **Hapus** | Hanya di edit | Menghapus opname (hanya jika status draft) |
| **Batal** | Create & edit | Kembali ke daftar |

---

## 6. Status Workflow

```
DRAFT ──→ SELESAI
  ↑            │
  └──── Edit ──┘ (hanya jika draft)
  
  SELESAI = Final, tidak bisa diedit lagi.
  Jika ingin mengubah opname yang sudah selesai:
  → Buat opname penyesuaian baru.
```

- **Draft**: Data tersimpan, stok belum terpengaruh. Bisa diedit.
- **Selesai**: Data final, stok sudah disesuaikan. Tidak bisa diedit — jika masuk halaman Edit, akan redirect ke halaman View.

---

## 7. Halaman View

Halaman View menampilkan detail opname dalam tabel read-only:

| Kolom | Keterangan |
|-------|------------|
| Obat | Nama obat |
| Stok Sistem | Stok menurut sistem |
| Stok Fisik | Stok hasil hitungan fisik |
| Selisih | Warna: hijau (positif), merah (negatif), abu-abu (0) |
| Batch | Nomor batch |
| Expired | Tanggal kedaluwarsa |

---

## 8. Proses Saat "Selesai"

Ketika status opname diubah menjadi `selesai`, method `StokService::prosesOpnameSelesai()` dipanggil:

```
for each detail:
  jika selisih == 0 → skip
  jika selisih ≠ 0:
    1. Dapatkan StokFaskes / StokGudang berdasarkan fasilitas_id
    2. Increment jumlah stok sebesar selisih
    3. Jika selisih > 0 & ada data batch:
         - Tambah ke BatchStok (create jika belum ada, increment jika sudah)
    4. Catat RiwayatStok:
         - tipe = 'opname'
         - jumlah = selisih
         - referensi = OpnameStok (polymorphic)
         - keterangan = "Opname: {nomor_opname} ({tipe})"
    5. Rekalkulasi batch aggregates (safety net)
```

### Reversal (Edit Opname yang Sudah Selesai)

Jika pengguna mengedit opname yang sudah berstatus `selesai` (seharusnya di-redirect, namun jika diproses):

```
1. Kembalikan stok ke kondisi sebelum opname (reverse)
   - Decrement stok agregat sebesar -selisih
   - Jika selisih > 0, kurangi batch
   - Catat RiwayatStok dengan tipe 'penyesuaian'
2. Proses data baru dengan prosesOpnameSelesai()
```

---

## 9. Hak Akses & Policy

### Policy: `OpnameStokPolicy`

| Method | Permission |
|--------|-----------|
| `viewAny()` / `view()` | `view_opname_stok` |
| `create()` | `create_opname_stok` |
| `update()` | `update_opname_stok` |
| `delete()` | `delete_opname_stok` |

### Permissions per Role

| Role | view | create | update | delete |
|------|------|--------|--------|--------|
| `super_admin` | ✓ | ✓ | ✓ | ✓ |
| `admin_gudang` | ✓ | ✓ | ✓ | — |
| `admin_dinas` | ✓ | — | ✓ | — |
| `puskesmas` | ✓ | ✓ | ✓ | ✓ |
| `pustu` | — | — | — | — |

Policy didaftarkan di `AuthServiceProvider`:
```php
OpnameStok::class => OpnameStokPolicy::class,
```

---

## 10. Activity Log

- **CRUD otomatis**: Via trait `LogsActivity` pada model `OpnameStok` — mencatat event `created`, `updated`, `deleted` ke log `master_data`
- **Log spesifik opname**: Method `ActivityLogService::buatOpname()` dan `selesaiOpname()` tersedia (saat ini belum dipanggil)
- **Filter di activity logs**: `opname_stok` tersedia sebagai filter kategori, dengan badge warna `danger`
- **RiwayatStok**: Setiap perubahan stok akibat opname tercatat di tabel `riwayat_stok` dengan `tipe = 'opname'`

---

## 11. File Structure

```
app/
├── Filament/Resources/OpnameStoks/
│   ├── OpnameStokResource.php           # Resource definition
│   ├── Pages/
│   │   ├── ListOpnameStoks.php          # Daftar opname
│   │   ├── CreateOpnameStok.php         # Buat opname baru
│   │   ├── EditOpnameStok.php           # Edit opname (draft only)
│   │   └── ViewOpnameStok.php           # Lihat detail opname
│   ├── Schemas/
│   │   └── OpnameStokForm.php           # Form schema (3 tabs)
│   └── Tables/
│       └── OpnameStoksTable.php         # List table definition
├── Models/
│   ├── OpnameStok.php                   # Model utama
│   └── DetailOpnameStok.php             # Model detail
├── Policies/
│   └── OpnameStokPolicy.php             # Authorization policy
├── Services/
│   └── StokService.php                  # Business logic (prosesOpnameSelesai, reverseOpname)
└── Helpers/
    └── BatchNumberGenerator.php         # Auto-generate batch number

database/
├── migrations/
│   ├── 2026_05_21_140019_create_opname_stok_table.php
│   ├── 2026_05_21_140020_create_detail_opname_stok_table.php
│   ├── 2026_05_23_024105_update_opname_stok_add_tipe_and_batch.php
│   ├── 2026_05_31_050124_rename_stok_baru_to_stok_awal_in_opname_stok.php
│   ├── 2026_05_31_065938_add_stok_baru_to_opname_stok.php
│   └── 2026_06_07_120149_add_batch_id_to_detail_opname_stok_table.php
└── seeders/
    └── RoleAndPermissionSeeder.php      # Permission seeding
```

---

## 13. Routing

Resource terdaftar di Filament panel dengan konfigurasi:

| Properti | Nilai |
|----------|-------|
| Slug | `/stok-opname` |
| Navigation Group | `Inventory` |
| Navigation Icon | `heroicon-o-clipboard-document-list` |
| Navigation Label | `Stok Opname` |

| Halaman | Route |
|---------|-------|
| List (index) | `/admin/stok-opname` |
| Create | `/admin/stok-opname/create` |
| View | `/admin/stok-opname/{record}` |
| Edit | `/admin/stok-opname/{record}/edit` |

---

## 14. Penggunaan

### Membuat Opname Baru

1. Navigasi ke menu **Inventory → Stok Opname**
2. Klik tombol **Buat Opname**
3. Pilih tab sesuai kebutuhan:
   - **Penyesuaian**: Untuk mencocokkan stok fisik dengan sistem
   - **Stok Awal**: Untuk input stok pertama kali
   - **Stok Baru**: Untuk menambahkan stok obat baru
4. Generate nomor opname (klik icon refresh) atau biarkan terisi otomatis saat simpan
5. Tambahkan item obat satu per satu
6. Klik **Simpan** (draft) atau **Selesai** (final)

### Melihat & Mengedit Opname

- Klik record di daftar untuk melihat detail
- Jika status masih **Draft**, bisa diedit
- Jika status **Selesai**, hanya bisa dilihat

### Menghapus Opname

- Hanya bisa menghapus opname dengan status **Draft**
- Gunakan tombol **Hapus** di halaman edit

---

## 15. Catatan Teknis

- Semua CRUD melalui Filament — tidak ada controller atau API route
- Fitur Teams pada Spatie Permission **dinonaktifkan**
- Penggunaan trait `LogsActivity` pada model akan otomatis mencatat aktivitas CRUD
- Form menggunakan teknik `dehydrated` kondisional — hanya data dari tab aktif yang dikirim ke server
- `required()` pada field hanya berlaku jika tab yang bersangkutan aktif

## 16. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 17. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/OpnameStoks/OpnameStokResource.php`
- `app/Filament/Resources/OpnameStoks/Pages\ListOpnameStoks.php`
- `app/Filament/Resources/OpnameStoks/Pages\ViewOpnameStok.php`
- `app/Filament/Resources/OpnameStoks/Schemas\OpnameStokForm.php`
- `app/Filament/Resources/OpnameStoks/Tables\OpnameStoksTable.php`
- `app/Models/OpnameStok.php`
- `app/Policies/OpnameStokPolicy.php`
