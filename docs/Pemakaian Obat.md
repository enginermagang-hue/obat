# Pemakaian Obat — Dokumentasi Fitur

## 1. Tujuan

Mengelola **pencatatan pemakaian obat** di fasilitas kesehatan (puskesmas/pustu). Pemakaian merekam obat apa saja yang digunakan dalam pelayanan kesehatan, beserta informasi pelayanan dan data pasien (RME). Setiap pemakaian akan **mengurangi stok** secara otomatis melalui mekanisme batch alokasi FEFO/FIFO/LIFO sesuai metode stok masing-masing obat.

Pemakaian obat bersifat **final terhadap stok** — berbeda dengan distribusi yang masih bisa diretur, pemakaian langsung mengurangi stok faskes saat disimpan.

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
┌──────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│  PemakaianObat   │────▶│  DetailPemakaianObat │────▶│  BatchStok           │
│  (Header)        │     │  (Detail items)      │     │  (Batch stock)       │
│  - nomor_pemakaian│     │  - obat_id          │     │  - decrement jumlah  │
│  - fasilitas_id  │     │  - batch_id (FEFO)  │     └──────────────────────┘
│  - tanggal       │     │  - jumlah            │              │
│  - jenis_pelayan │     │  - dosis             │              ▼
│  - nama_pasien   │     │  - satuan_dosis      │     ┌──────────────────────┐
│  - no_rekam_medis│     └──────────────────────┘     │  StokFaskes /        │
│  - diagnosa_kode │                                   │  StokGudang          │
│  - user_id       │                                   │  (Aggregate stock)   │
└──────────────────┘                                   └──────────────────────┘
                                                               │
                                                               ▼
                                                      ┌──────────────────┐
                                                      │  RiwayatStok     │
                                                      │  (Audit trail)   │
                                                      └──────────────────┘
```

**Alur stok saat pemakaian dicatat:**

1. **Pilih obat** → sistem menggunakan `FefoService::allocate()` untuk mengalokasikan batch secara otomatis (FEFO/FIFO/LIFO sesuai metode stok obat)
2. **Simpan pemakaian** → `StokService::prosesPemakaian()`:
   - Kurangi `StokFaskes` atau `StokGudang` (agregat per-obat)
   - Kurangi `BatchStok` per-batch yang dialokasikan
   - Catat `RiwayatStok` tipe `keluar`
   - Safety net: `BatchStok::recalculateFaskes()` / `recalculateGudang()`
3. **Edit/Hapus** → `StokService::reversePemakaian()`: mengembalikan stok ke kondisi sebelum pemakaian

### 3.2 Relasi Model

| Model                  | Relasi            | Type           | Target                 |
| ---------------------- | ----------------- | -------------- | ---------------------- |
| `PemakaianObat`        | `fasilitas()`     | BelongsTo      | `FasilitasKesehatan`   |
| `PemakaianObat`        | `user()`          | BelongsTo      | `User`                 |
| `PemakaianObat`        | `details()`       | HasMany        | `DetailPemakaianObat`  |
| `PemakaianObat`        | `riwayatStok()`   | HasManyThrough | `RiwayatStok` (via DetailPemakaianObat) |
| `DetailPemakaianObat`  | `pemakaian()`     | BelongsTo      | `PemakaianObat`        |
| `DetailPemakaianObat`  | `obat()`          | BelongsTo      | `Obat`                 |
| `DetailPemakaianObat`  | `batch()`         | BelongsTo      | `BatchStok`            |

### 3.3 Struktur Tabel Database

**Tabel `pemakaian_obat`** (header):

| Kolom              | Tipe          | Keterangan                                  |
| ------------------ | ------------- | ------------------------------------------- |
| `id`               | bigint (PK)   | Auto-increment                              |
| `nomor_pemakaian`  | varchar(50)   | Format: `PMK-{FASKES}-{YYYYMM}-{Urut:4}`   |
| `fasilitas_id`     | bigint (FK)   | → `fasilitas_kesehatan.id`                  |
| `tanggal_pemakaian`| date          | Tanggal pemakaian (max: hari ini)           |
| `jenis_pelayanan`  | enum          | rawat_jalan, rawat_inap, uks, posyandu, pusling, gigi, laboratorium, apotek, lainnya |
| `nama_pasien`      | varchar(255)  | Nama pasien (opsional)                      |
| `no_rekam_medis`   | varchar(50)   | Nomor rekam medis (opsional)                |
| `diagnosa_kode`    | varchar(100)  | Kode ICD-10 (opsional)                      |
| `user_id`          | bigint (FK)   | → `users.id` (petugas pencatat)             |
| `catatan`          | text          | Catatan umum (opsional)                     |
| `created_at`       | datetime      |                                              |
| `updated_at`       | datetime      |                                              |

Index: `idx_pemakaian_header_v2` (fasilitas_id, tanggal_pemakaian, jenis_pelayanan)
Unique: `pemakaian_obat_nomor_unique` (nomor_pemakaian)

**Tabel `detail_pemakaian_obat`** (detail items):

| Kolom            | Tipe          | Keterangan                                |
| ---------------- | ------------- | ----------------------------------------- |
| `id`             | bigint (PK)   | Auto-increment                            |
| `pemakaian_id`   | bigint (FK)   | → `pemakaian_obat.id` (cascade delete)    |
| `obat_id`        | bigint (FK)   | → `obat.id` (restrict on delete)          |
| `batch_id`       | bigint (FK)   | → `batch_stok.id` (null on delete)        |
| `jumlah`         | unsigned int  | Jumlah pakai                               |
| `dosis`          | varchar(100)  | Dosis (opsional, contoh: `3x1 sehari`)    |
| `satuan_dosis`   | varchar(50)   | Satuan (tablet, kapsul, sirup, dll)        |
| `catatan`        | text          | Catatan per-item (opsional)               |
| `created_at`     | datetime      |                                            |
| `updated_at`     | datetime      |                                            |

Index: `idx_detail_pemakaian_obat` (pemakaian_id, obat_id)

---

## 4. Algoritma Kunci

### 4.1 Auto-generate Nomor Pemakaian

**Format default:** `PMK-{KODE_FASKES}-{YYYYMM}-{Urut:4}`

Contoh: `PMK-PKM01-202606-0001`

**Mekanisme:**

```
PemakaianObat::generateNomorPemakaian($tanggal, $fasilitasId)
    ↓
NomorFormatService::generate('pemakaian_obat', $fasilitasId, $tanggal)
    ↓
Membaca pola dari PengaturanLaporan atau default
    ↓
Meresolve placeholder:
    {FASKES} → kode_faskes (atau 'GUD' jika null)
    {YYYYMM} → 202606
    {Urut:4} → 0001 (auto-increment dari nomor terakhir)
```

**File terkait:**
- `app/Models/PemakaianObat.php` — method `generateNomorPemakaian()`
- `app/Services/NomorFormatService.php` — engine generasi nomor

### 4.2 Auto-allocation Batch (FEFO/FIFO/LIFO)

Saat user menambahkan item obat, sistem **tidak menampilkan dropdown batch** untuk dipilih. Sebaliknya, `FefoService::allocate()` secara otomatis mengalokasikan batch berdasarkan metode stok masing-masing obat (dari kolom `obat.metode_stok`).

**Mekanisme (`ManagesPemakaianDetails::addItem()`):**

```php
// 1. Dapatkan metode stok obat
$metode = $obat->metode_stok->value; // 'fefo' | 'fifo' | 'lifo'

// 2. Alokasikan batch via FefoService
$service = app(FefoService::class);
$allocations = $service->allocate($obatId, $jumlah, $fasilitasId, $metode);

// 3. Validasi total alokasi
$allocatedTotal = collect($allocations)->sum('jumlah');

if ($allocatedTotal < $jumlah) {
    // Stok tidak cukup → tampilkan warning
    return;
}

// 4. Buat satu baris detail per batch yang dialokasikan
foreach ($allocations as $allocation) {
    $this->details[] = [
        'obat_id' => $obatId,
        'batch_id' => $allocation['batch_id'],
        'jumlah' => $allocation['jumlah'],
        // ... dosis, satuan, catatan
    ];
}
```

**Metode alokasi (`FefoService::getAvailableBatches()`):**

| Metode | Urutan                        | Kolom Order        |
| ------ | ----------------------------- | ------------------ |
| FEFO   | Tanggal expired terdekat dulu | `tanggal_expired`  |
| FIFO   | Tanggal masuk terlama dulu    | `tanggal_masuk`    |
| LIFO   | Tanggal masuk terbaru dulu    | `tanggal_masuk DESC` |

> **Catatan:** Satu obat bisa menghasilkan **lebih dari satu baris detail** jika kuantitasnya harus dipenuhi dari beberapa batch (misal: minta 100 unit, batch A hanya punya 60, sisanya 40 dari batch B).

### 4.3 Mutasi Stok Saat Create (`StokService::prosesPemakaian()`)

Setelah record pemakaian + details tersimpan di database, `afterCreate()` memanggil `StokService::prosesPemakaian()`:

```php
public function prosesPemakaian(PemakaianObat $pemakaian): void
{
    foreach ($pemakaian->details as $detail) {
        // 1. Kurangi stok agregat
        $stok = $this->getStokTarget($fasilitasId, $detail->obat_id);
        $stokSebelum = $stok->jumlah;
        $stok->decrement('jumlah', $detail->jumlah);

        // 2. Kurangi batch_stok
        if ($detail->batch_id) {
            $batch->decrement('jumlah', $detail->jumlah);
            if ($batch->jumlah <= 0) {
                $batch->update(['status' => 'dimusnahkan']);
            }
        }

        // 3. Catat RiwayatStok (polymorphic ke DetailPemakaianObat)
        $this->catatRiwayat(
            fasilitasId: $fasilitasId,
            obatId: $detail->obat_id,
            tipe: 'keluar',
            jumlah: -$detail->jumlah,
            stokSebelum: $stokSebelum,
            referensi: $detail,  // ← DetailPemakaianObat
            userId: $pemakaian->user_id,
            keterangan: 'Pemakaian: '.$pemakaian->nomor_pemakaian,
            tanggal: $pemakaian->tanggal_pemakaian,
        );

        // 4. Safety net: sync aggregate
        BatchStok::recalculateFaskes($fasilitasId, $detail->obat_id);
    }
}
```

**Target stok (agregat):**

| Kondisi                  | Tabel target   |
| ------------------------ | -------------- |
| Pemakaian di faskes      | `stok_faskes`  |
| Pemakaian di gudang      | `stok_gudang`  |

### 4.4 Reverse Stok Saat Edit / Hapus (`StokService::reversePemakaian()`)

Saat record diedit atau dihapus, stok dikembalikan ke kondisi sebelum pemakaian:

```php
public function reversePemakaian(PemakaianObat $pemakaian): void
{
    foreach ($pemakaian->details as $detail) {
        // 1. Tambah kembali stok agregat
        $stok->increment('jumlah', $detail->jumlah);

        // 2. Tambah kembali batch_stok
        if ($detail->batch_id) {
            $batch->increment('jumlah', $detail->jumlah);
            // Re-aktifkan batch jika sebelumnya 'dimusnahkan'
            if ($batch->status === 'dimusnahkan' && $batch->jumlah > 0) {
                $batch->update(['status' => 'tersedia']);
            }
        }

        // 3. Catat RiwayatStok reversal (tipe 'masuk')
        $this->catatRiwayat(
            tipe: 'masuk',
            jumlah: $detail->jumlah,  // positif → stok naik
            keterangan: 'Pembatalan pemakaian: '.$pemakaian->nomor_pemakaian,
            // ...
        );
    }
}
```

**Alur edit lengkap:**

```
1. EditPemakaianObat.afterSave():
   ↓
2. StokService::reversePemakaian($originalRecord)
   → Kembalikan stok ke kondisi sebelum pemakaian awal
   ↓
3. Hapus detail yang tidak ada di data baru
   ↓
4. Insert/update detail sesuai data baru
   ↓
5. StokService::prosesPemakaian($updatedRecord)
   → Kurangi stok dengan data baru
```

---

## 5. Form Schema (`PemakaianObatForm.php`)

Menggunakan pattern `static configure(Schema): Schema`. Layout satu kolom dengan beberapa section.

### Section: Informasi Pelayanan

| Field                | Type        | Keterangan                                             |
| -------------------- | ----------- | ------------------------------------------------------ |
| `fasilitas_id`       | Hidden      | Otomatis dari `$user->fasilitas_kesehatan_id`          |
| `tanggal_pemakaian`  | DatePicker  | Required, default: `now()`, max: `now()`               |
| `jenis_pelayanan`    | Select      | Required — rawat_jalan, rawat_inap, uks, posyandu, pusling, gigi, laboratorium, apotek, lainnya |
| `user_id`            | Hidden      | Otomatis dari `$user->id`                              |

### Section: Data Pasien (RME)

| Field              | Type       | Keterangan                                            |
| ------------------ | ---------- | ----------------------------------------------------- |
| `nama_pasien`      | TextInput  | Opsional, max 255                                     |
| `no_rekam_medis`   | TextInput  | Opsional, max 50                                      |
| `diagnosa_kode`    | TextInput  | Opsional, kode ICD-10, max 100                        |
| `catatan`          | Textarea   | Opsional, 2 baris                                     |

### Section: Detail Obat

Menggunakan **embedded table** (bukan Repeater) dengan komponen `EmbeddedTable` yang di-render via trait `ManagesPemakaianDetails`.

| Field           | Tipe Komponen | Keterangan                                              |
| --------------- | ------------- | ------------------------------------------------------- |
| Pilih Obat      | Select        | Hanya obat dgn stok > 0 di faskes/gudang, searchable   |
| Jumlah Pakai    | TextInput     | Numeric, min:1, max: stok tersedia                      |
| Dosis           | TextInput     | Opsional, contoh: `3x1 sehari`                          |
| Satuan Dosis    | Select        | Opsional: tablet/kapsul/sirup/tetes/salep/injeksi/dll  |
| Catatan per Item| Textarea      | Opsional                                                |

---

## 6. Manajemen Detail Items (`ManagesPemakaianDetails` Trait)

Trait ini digunakan oleh `CreatePemakaianObat` dan `EditPemakaianObat` untuk mengelola detail items dalam bentuk array in-memory (`$this->details`), bukan langsung ke database.

### Struktur Data In-Memory

```php
$this->details = [
    [
        '_key' => 0,              // key unik untuk identifikasi baris
        'id' => null,             // ID database (null untuk item baru)
        'obat_id' => 1,
        'obat_name' => 'Paracetamol 500mg',
        'batch_id' => 5,
        'batch_number' => 'BN202606001',
        'jumlah' => 100,
        'dosis' => '3x1 sehari',
        'satuan_dosis' => 'tablet',
        'catatan' => null,
    ],
    // ... bisa lebih dari satu baris per obat jika multi-batch
];
```

### Table Actions

| Action           | Trigger              | Keterangan                                        |
| ---------------- | -------------------- | ------------------------------------------------- |
| `addItem`        | Header: Tambah Obat  | Modal form → alokasi batch otomatis via FefoService |
| `editItem`       | Row: Edit            | Modal form (obat_id disabled, tidak bisa ganti obat) |
| `deleteItem`     | Row: Hapus           | Konfirmasi → hapus dari array                     |

### Filter Obat yang Tersedia

Obat yang muncul di dropdown **hanya yang memiliki stok > 0** di fasilitas user:

```php
// Super admin / admin_gudang: obat dengan stok_gudang > 0
// Puskesmas/pustu: obat dengan stok_faskes > 0 di faskesnya
Obat::where('status', 'aktif')
    ->where(function ($q) use ($fasilitasId, $isSuperAdmin, $isAdminGudang) {
        if ($isSuperAdmin || $isAdminGudang) {
            $q->whereHas('stokGudang', fn ($sq) => $sq->where('jumlah', '>', 0));
        } elseif (filled($fasilitasId)) {
            $q->whereHas('stokFaskes', fn ($sq) => $sq
                ->where('fasilitas_id', $fasilitasId)
                ->where('jumlah', '>', 0)
            );
        }
    })
```

### Konfirmasi Sebelum Simpan

Tombol "Simpan Pemakaian" memunculkan modal konfirmasi dengan ringkasan:

```
{totalItems} item obat (total {totalQty} unit)

Obat: Paracetamol 500mg, Amoxicillin 250mg, ... 

Stok akan berkurang otomatis saat disimpan.
```

Tombol akan **disabled** jika belum ada item (`empty($this->details)`).

---

## 7. Tabel & Filter (`PemakaianObatsTable.php`)

### Kolom

| Kolom                        | Type     | Keterangan                              |
| ---------------------------- | -------- | --------------------------------------- |
| `nomor_pemakaian`            | Text     | Sortable, searchable, copyable          |
| `tanggal_pemakaian`          | Date     | Sortable, format `d/m/Y`                |
| `fasilitas.nama`             | Text     | Sortable, searchable, toggleable (hidden untuk puskesmas/pustu) |
| `jenis_pelayanan`            | Badge    | Warna sesuai jenis (lihat tabel warna)  |
| `nama_pasien`                | Text     | Searchable, wrap, description: NRM      |
| `no_rekam_medis`             | Text     | Searchable, toggleable, hidden default  |
| `details_count`              | Count    | Badge info — jumlah item obat           |
| `details_sum_jumlah`         | Sum      | Total kuantitas seluruh item            |
| `diagnosa_kode`              | Text     | Searchable, toggleable, hidden default  |
| `user.name`                  | Text     | Toggleable, hidden default              |
| `catatan`                    | Text     | Limit 40, toggleable, hidden default    |
| `created_at`                 | DateTime | Format `d/m/Y H:i`, sortable, toggleable, hidden default |

**Default sort:** `tanggal_pemakaian DESC`

### Warna Badge Jenis Pelayanan

| Jenis          | Color     |
| -------------- | --------- |
| Rawat Jalan    | `primary` |
| Rawat Inap     | `info`    |
| UKS            | `warning` |
| Posyandu       | `success` |
| Pusling        | `warning` |
| Poli Gigi      | `info`    |
| Laboratorium   | `gray`    |
| Apotek         | `primary` |
| Lainnya        | `gray`    |

### Filters

| Filter                        | Type         | Keterangan                                     |
| ----------------------------- | ------------ | ---------------------------------------------- |
| **Jenis Pelayanan**           | SelectFilter | Multiple select                                |
| **Fasilitas**                 | SelectFilter | Hanya visible untuk non-puskesmas/pustu        |
| **Periode**                   | Filter       | Date range: Dari & Sampai, default: kuartal ini |

### Record Actions

| Action     | Keterangan                           |
| ---------- | ------------------------------------ |
| View       | Lihat detail (infolist dengan tabs)  |
| Edit       | Edit record (visible: policy-driven) |

**Kondisi Edit visible:**

```php
// super_admin: bisa edit semua
// puskesmas/pustu: hanya record faskes sendiri + tanggal_pemakaian = hari ini
```

### Tabs (tidak ada tabs di List page, hanya header action create)

---

## 8. Pages

| Page                | File                                       | Keterangan                                        |
| ------------------- | ------------------------------------------ | ------------------------------------------------- |
| ListPemakaianObats  | `Pages/ListPemakaianObats.php`             | Daftar pemakaian + tombol create                  |
| CreatePemakaianObat | `Pages/CreatePemakaianObat.php`            | Create dengan auto-batch allocation               |
| ViewPemakaianObat   | `Pages/ViewPemakaianObat.php`              | Infolist dengan 3 tabs: Informasi, Detail Obat, Riwayat Stok |
| EditPemakaianObat   | `Pages/EditPemakaianObat.php`              | Edit dengan reverse stok + reapply                |

### Detail Page (ViewPemakaianObat) — 3 Tabs

**Tab 1: Informasi & Pasien**

Sections:
- **Informasi Pelayanan** — nomor pemakaian, tanggal, created_at, fasilitas, jenis pelayanan (badge), pencatat
- **Data Pasien (RME)** — nama pasien, no RM, kode ICD-10
- **Catatan** — catatan umum (jika ada)

**Tab 2: Detail Obat**

- `RepeatableEntry` untuk setiap item detail
- Grid 6 kolom: Nama Obat, Kode Obat, Batch Number, Jumlah (bold), Dosis, Satuan
- Badge count di tab: jumlah item obat

**Tab 3: Riwayat Stok**

- `RepeatableEntry` untuk `$record->riwayatStok` (HasManyThrough via DetailPemakaianObat)
- Menampilkan perubahan stok: Nama Obat, Tipe (badge), Jumlah (±), Stok (sebelum → sesudah), Oleh, Tanggal, Keterangan
- Badge count di tab: jumlah riwayat

### Edit Actions (EditPemakaianObat)

**Header actions:**
- **Hapus** — icon trash, dengan konfirmasi, memanggil `reversePemakaian()` sebelum delete

**Form actions:**
- **Batal** — kembali ke halaman sebelumnya
- **Simpan Perubahan** — konfirmasi dengan ringkasan, lalu: reverse stok lama → simpan detail → aplikasikan stok baru

---

## 9. Permission & Policy

### Permission

Semua permission `pemakaian_obat` dibuat oleh `RoleAndPermissionSeeder` via loop:

```
view_pemakaian_obat
create_pemakaian_obat
update_pemakaian_obat
delete_pemakaian_obat
```

### Assignment ke Role

| Permission                    | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ----------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_pemakaian_obat`         |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create_pemakaian_obat`       |     ✅      |     ❌       |     ❌      |    ✅     |  ✅   |
| `update_pemakaian_obat`       |     ✅      |     ❌       |     ❌      |    ✅     |  ✅   |
| `delete_pemakaian_obat`       |     ✅      |     ❌       |     ❌      |    ✅     |  ✅   |

### Policy Rules (`PemakaianObatPolicy`)

| Method     | super_admin        | admin_dinas/gudang     | puskesmas/pustu                         |
| ---------- | ------------------ | ---------------------- | --------------------------------------- |
| `viewAny`  | ✅ (hasPermission) | ✅ (hasPermission)     | ✅ (hasPermission)                      |
| `view`     | ✅ (semua)         | ✅ (semua)             | ✅ (hanya milik faskes sendiri)         |
| `create`   | ✅ (punya faskes?) | ❌ (tidak punya faskes)| ✅ (harus punya faskes)                 |
| `update`   | ✅ (semua)         | ❌                     | ✅ (faskes sendiri + tanggal = hari ini)|
| `delete`   | ✅ (semua)         | ❌                     | ✅ (faskes sendiri + tanggal = hari ini)|

> **Catatan:** `admin_gudang` dan `admin_dinas` (Dinas/Gudang) tidak punya `fasilitas_kesehatan_id`, sehingga `create()` akan return false meskipun punya permission.

### Scope Query per Role

Di `PemakaianObatResource::getEloquentQuery()`:

```php
// Role dengan fasilitas (puskesmas/pustu): hanya lihat pemakaian milik faskesnya
if (filled($user->fasilitas_kesehatan_id)) {
    $query->where('fasilitas_id', $user->fasilitas_kesehatan_id);
}

// Super admin / admin_gudang / admin_dinas: lihat semua record
```

### Policy Registration

Policy didaftarkan secara eksplisit di `app/Providers/AuthServiceProvider.php`:

```php
PemakaianObat::class => PemakaianObatPolicy::class,
```

---

## 10. Riwayat Stok

Setiap mutasi stok dari pemakaian dicatat di tabel `riwayat_stok` dengan struktur:

| Kolom              | Value                                          |
| ------------------ | ---------------------------------------------- |
| `fasilitas_id`     | Faskes yang stoknya berubah                    |
| `obat_id`          | Obat yang dipakai                              |
| `tipe`             | `keluar` (pemakaian) / `masuk` (reversal)      |
| `jumlah`           | Negatif untuk keluar, positif untuk reversal   |
| `stok_sebelum`     | Stok sebelum pemakaian                         |
| `stok_sesudah`     | Stok setelah pemakaian                         |
| `referensi_type`   | `App\Models\DetailPemakaianObat`               |
| `referensi_id`     | ID dari DetailPemakaianObat terkait            |
| `user_id`          | Petugas yang mencatat                          |
| `keterangan`       | `Pemakaian obat: PMK-PKM01-202606-0001 (Rawat Jalan)` |
| `tanggal`          | Tanggal pemakaian                              |

Relasi `riwayatStok()` di model `PemakaianObat` menggunakan `HasManyThrough`:

```php
public function riwayatStok(): HasManyThrough
{
    return $this->hasManyThrough(
        RiwayatStok::class,
        DetailPemakaianObat::class,
        'pemakaian_id',       // FK di detail_pemakaian_obat
        'referensi_id',       // FK di riwayat_stok
        'id',                 // PK di pemakaian_obat
        'id',                 // PK di detail_pemakaian_obat
    )->where('riwayat_stok.referensi_type', DetailPemakaianObat::class);
}
```

---

## 11. Cara Penggunaan

### Membuat Pemakaian Obat Baru

1. Buka menu **Distribusi & Permintaan** → **Pemakaian Obat**
2. Klik tombol **"Buat Pemakaian"**
3. Isi **Informasi Pelayanan**:
   - Tanggal Pemakaian (default: hari ini)
   - Jenis Pelayanan (pilih dari dropdown)
4. Isi **Data Pasien** (opsional):
   - Nama Pasien
   - No. Rekam Medis
   - Kode Diagnosa (ICD-10)
5. Klik **"Tambah Obat"** pada tabel detail:
   - Pilih **Obat** (hanya obat dengan stok > 0 yang muncul)
   - Masukkan **Jumlah Pakai**
   - Isi **Dosis** dan **Satuan Dosis** (opsional)
   - Isi **Catatan per Item** (opsional)
   - Simpan
   > **Catatan:** Batch akan dialokasikan otomatis berdasarkan metode stok obat (FEFO/FIFO/LIFO). Satu obat bisa menghasilkan lebih dari satu baris jika harus mengambil dari beberapa batch.
6. Ulangi langkah 5 untuk menambah item lain
7. Klik **"Simpan Pemakaian"**:
   - Konfirmasi akan muncul dengan ringkasan item
   - Setelah dikonfirmasi, stok akan **berkurang otomatis**

### Melihat Detail Pemakaian

Klik **"Lihat Detail"** pada baris pemakaian:

- **Tab Informasi & Pasien**: Data header pemakaian, data pasien, catatan
- **Tab Detail Obat**: Daftar obat yang dipakai lengkap dengan batch, jumlah, dosis
- **Tab Riwayat Stok**: Perubahan stok yang terjadi akibat pemakaian ini

### Mengedit Pemakaian

1. Buka detail pemakaian → klik **"Edit"**
2. Ubah data yang diperlukan (tanggal pemakaian tidak bisa diubah)
3. Untuk mengubah item obat:
   - Edit jumlah, dosis, atau catatan
   - Hapus item yang tidak diperlukan
   - Tambah item baru
4. Klik **"Simpan Perubahan"**:
   - Sistem akan **mengembalikan stok lama** → **menerapkan stok baru**
   - Riwayat stok akan tercatat untuk reversal dan pemakaian baru

### Menghapus Pemakaian

1. Buka halaman edit pemakaian
2. Klik ikon **"Hapus"** di header
3. Konfirmasi penghapusan
4. Stok akan **dikembalikan** ke kondisi sebelum pemakaian

---

## 12. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/PemakaianObats/Concerns\ManagesPemakaianDetails.php`
- `app/Filament/Resources/PemakaianObats/Pages\CreatePemakaianObat.php`
- `app/Filament/Resources/PemakaianObats/Pages\EditPemakaianObat.php`
- `app/Filament/Resources/PemakaianObats/Pages\ListPemakaianObats.php`
- `app/Filament/Resources/PemakaianObats/Pages\ViewPemakaianObat.php`
- `app/Filament/Resources/PemakaianObats/PemakaianObatResource.php`
- `app/Filament/Resources/PemakaianObats/Schemas\PemakaianObatForm.php`
- `app/Filament/Resources/PemakaianObats/Tables\PemakaianObatsTable.php`
- `app/Models/PemakaianObat.php`
- `app/Policies/PemakaianObatPolicy.php`

## 13. Catatan Penting

1. **Pemakaian bersifat final terhadap stok** — tidak ada mekanisme "retur" untuk pemakaian seperti pada distribusi. Jika ada kesalahan, lakukan edit/hapus untuk mengembalikan stok.
2. **Satu pemakaian bisa memiliki banyak baris detail** untuk obat yang sama — jika kuantitas melebihi stok batch terbaik, `FefoService` akan mengambil dari batch berikutnya.
3. **Tanggal pemakaian tidak bisa di masa depan** — dibatasi `->maxDate(now())`.
4. **Edit hanya diizinkan untuk hari yang sama** — policy `update()` dan `delete()` mewajibkan `tanggal_pemakaian->isToday()` untuk non-super_admin.
5. **Data pasien bersifat opsional** — pemakaian bisa dicatat tanpa nama pasien (misal: pemakaian untuk pelayanan non-pasien seperti UKS atau pusling).
6. **LogsActivity** — semua perubahan pada PemakaianObat dan DetailPemakaianObat dicatat oleh trait `LogsActivity`.

---

*Dokumentasi ini selaras dengan `docs/Skema Database.md`, `docs/Distribusi Obat.md`, dan `docs/Permintaan Obat.md`.*
