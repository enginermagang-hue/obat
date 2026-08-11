# Distribusi Obat — Dokumentasi Fitur

## 1. Tujuan

Mengelola **distribusi obat** dari hasil permintaan yang telah disetujui, dengan alur distribusi bertingkat sesuai hierarki fasilitas kesehatan:

1. **Dinas → Puskesmas** (`dinas_ke_puskesmas`): admin_gudang (Gudang Dinas) mendistribusikan obat ke Puskesmas
2. **Puskesmas → Pustu** (`puskesmas_ke_pustu`): role `puskesmas` mendistribusikan obat ke Pustu di bawahnya

Setiap distribusi memiliki **nomor surat jalan** unik, mencatat **batch dan tanggal expired** setiap obat (FEFO), dan dapat dicetak sebagai **faktur PDF**.

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
│  PermintaanObat  │────▶│  DistribusiObat    │────▶│  DetailDistribusiObat │
│  status=disetujui│     │                    │     │  (Repeater items)     │
└──────────────────┘     │  nomor_surat_jalan │     │  - obat_id            │
                         │  tipe_distribusi   │     │  - batch_id (FEFO)   │
                         │  fasilitas_*       │     │  - jumlah             │
                         │  status (4 state)  │     └───────────────────────┘
                         │  tanggal_kirim     │
                         │  tanggal_terima    │
                         └────────┬───────────┘
                                  │
                         ┌────────▼───────────┐
                         │  ReturObat         │
                         │  (jika ada)        │
                         └────────────────────┘
```

### 3.2 Relasi Model

| Model                          | Relasi                       | Type      | Target                 |
| ------------------------------ | ---------------------------- | --------- | ---------------------- |
| `DistribusiObat`               | `permintaan()`               | BelongsTo | `PermintaanObat`       |
| `DistribusiObat`               | `fasilitasPengirim()`        | BelongsTo | `FasilitasKesehatan`   |
| `DistribusiObat`               | `fasilitasPenerima()`        | BelongsTo | `FasilitasKesehatan`   |
| `DistribusiObat`               | `pengirim()`                 | BelongsTo | `User`                 |
| `DistribusiObat`               | `penerima()`                 | BelongsTo | `User` (nullable)      |
| `DistribusiObat`               | `details()`                  | HasMany   | `DetailDistribusiObat` |
| `DistribusiObat`               | `returs()`                   | HasMany   | `ReturObat`            |
| `DetailDistribusiObat`         | `distribusi()`               | BelongsTo | `DistribusiObat`       |
| `DetailDistribusiObat`         | `obat()`                     | BelongsTo | `Obat`                 |
| `DetailDistribusiObat`         | `batch()`                    | BelongsTo | `BatchStok`            |
| `PermintaanObat` (from parent) | `distribusi()`               | HasMany   | `DistribusiObat`       |

### 3.3 Diagram Alur End-to-End

**Alur Pustu → Puskesmas (distribusi dari Puskesmas ke Pustu):**

```
PUSTU                     PUSKESMAS (role: puskesmas)      DINAS (role: admin_dinas)
  │                             │                               │
  │  1. Buat Permintaan         │                               │
  │  ─────────────────────────▶│                               │
  │  (pustu_ke_puskesmas)       │                               │
  │                             │                               │
  │                             │  2. Setujui/Tolak Permintaan  │
  │                             │  (Puskesmas approve sendiri)  │
  │                             │                               │
  │                             │  3. (Jika disetujui)          │
  │                             │  Klik "Buat Distribusi"       │
  │                             │                               │
  │                             │  4. Form Distribusi terisi    │
  │                             │  otomatis dari Permintaan     │
  │                             │                               │
  │                             │  5. Pilih Batch (FEFO)        │
  │                             │  + Jumlah Kirim               │
  │                             │                               │
  │                             │  6. Simpan/Kirim Distribusi   │
  │                             │                               │
  │  7. Terima Distribusi       │                               │
  │  ◀─────────────────────────│                               │
  │  (konfirmasi diterima)      │                               │
```

**Alur Puskesmas → Dinas (distribusi dari Gudang Dinas ke Puskesmas):**

```
PUSKESMAS                  GUDANG DINAS (role: admin_gudang)  DINAS (role: admin_dinas)
  │                             │                               │
  │  1. Buat Permintaan         │                               │
  │  ─────────────────────────────────────────────────────────▶│
  │  (puskesmas_ke_dinas)       │                               │
  │                             │                               │
  │                             │                               │  2. Setujui Permintaan
  │                             │                               │
  │                             │  3. (Jika disetujui)          │
  │                             │  Klik "Buat Distribusi"       │
  │                             │                               │
  │                             │  4. Form Distribusi terisi    │
  │                             │  otomatis, pilih batch (FEFO)│
  │                             │                               │
  │                             │  5. Kirim Distribusi          │
  │                             │                               │
  │  6. Terima Distribusi       │                               │
  │  ◀─────────────────────────│                               │
  │  (konfirmasi diterima)      │                               │
```

---

## 4. Algoritma Kunci

### 4.1 Pre-fill Distribusi dari Permintaan

Ketika user mengklik "Buat Distribusi" dari halaman detail permintaan, form create distribusi otomatis terisi dengan data permintaan yang disetujui.

**Mekanisme:**

```
DetailPermintaanObat              CreateDistribusiObat
       │                                 │
       │  URL: /create?permintaan_id=X   │
       │────────────────────────────────▶│
       │                                 │
       │                          #[Url] public ?int $permintaan_id
       │                          (Livewire auto-bind dari URL)
       │                                 │
       │                          fillForm():
       │                           1. $this->form->fill() ← defaults
       │                           2. Baca $this->permintaan_id
       │                           3. Load PermintaanObat + details
       │                           4. rawState([...existing...,
       │                              permintaan_id, fasilitas_penerima_id,
       │                              details: [obat_id, jumlah]])
       │                           5. hydrateState() ← propagate
```

**File terkait:**
- `DetailPermintaanObat.php` — action `buat_distribusi` → `DistribusiObatResource::getUrl('create', ['permintaan_id' => $id])`
- `CreateDistribusiObat.php` — `#[Url] public ?int $permintaan_id` + override `fillForm()`

**Kode kunci (`fillForm()`):**
```php
protected function fillForm(): void
{
    $this->callHook('beforeFill');
    $this->form->fill();                     // isi default dulu
    $this->callHook('afterFill');

    if (blank($this->permintaan_id)) {
        return;
    }

    $permintaan = PermintaanObat::with('details.obat')->find($this->permintaan_id);
    if (! $permintaan || $permintaan->status !== 'disetujui') {
        return;
    }

    $this->form->rawState([
        ...$this->form->getRawState(),       // preserve defaults (nomor_surat_jalan, etc)
        'permintaan_id' => $permintaan->id,
        'fasilitas_penerima_id' => $permintaan->fasilitas_pengirim_id,
        'details' => $permintaan->details->map(fn (DetailPermintaanObat $d): array => [
            'obat_id' => $d->obat_id,
            'jumlah' => $d->jumlah_disetujui ?? $d->jumlah_diminta,
        ])->toArray(),
    ]);
    $hydratedDefaultState = null;
    $this->form->hydrateState($hydratedDefaultState, shouldCallHydrationHooks: false);
}
```

### 4.2 Auto-generate Nomor Surat Jalan

**Format:** `SJ/{tahun}/{nomor_urut}` — contoh: `SJ/2026/001`

**Cara kerja:**
- Nomor di-generate otomatis via closure `default()` pada field `nomor_surat_jalan` di form schema
- Query nomor terakhir dengan prefix tahun berjalan → increment + 1
- Juga ada fallback di `mutateFormDataBeforeCreate()` untuk jaga-jaga

```php
$prefix = "SJ/{$year}/";
$lastNumber = DistribusiObat::query()
    ->where('nomor_surat_jalan', 'like', "{$prefix}%")
    ->orderBy('id', 'desc')
    ->value('nomor_surat_jalan');

// Parse sequence terakhir, increment
$nextSeq = $lastNumber
    ? (int) substr($lastNumber, strrpos($lastNumber, '/') + 1) + 1
    : 1;

return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
```

### 4.3 FEFO Batch Selection

**FEFO = First Expired First Out.** Batch dengan tanggal expired TERDEKAT ditampilkan paling atas.

**Mekanisme (`DistribusiObatForm::getBatchOptions()`):**

```php
return BatchStok::query()
    ->where('obat_id', $obatId)
    ->where('status', 'tersedia')
    ->where('jumlah', '>', 0)
    ->when(
        filled($fasilitasId),
        fn ($q) => $q->where('fasilitas_id', $fasilitasId),
        fn ($q) => $q->whereNull('fasilitas_id'), // NULL = Gudang Dinas
    )
    ->orderBy('tanggal_expired')  // <-- FEFO
    ->get()
    ->mapWithKeys(fn (BatchStok $batch): array => [
        $batch->id => sprintf(
            '%s (Exp: %s, Sisa: %s)',
            $batch->batch_number,
            $batch->tanggal_expired->format('d/m/Y'),
            number_format($batch->jumlah, 0, ',', '.'),
        ),
    ]);
```

**Display format:** `{batch_number} (Exp: {dd/mm/yyyy}, Sisa: {jumlah})`

### 4.4 Scope Batch oleh Fasilitas

Filter batch stok berdasarkan **fasilitas pengirim**:

| Role User         | `fasilitasId`                            | Batch yang ditampilkan                       |
| ----------------- | ---------------------------------------- | -------------------------------------------- |
| super_admin       | dari field `fasilitas_pengirim_id`       | Batch milik faskes yg dipilih di form        |
| admin_gudang      | `null` (Dinas, tanpa faskes)             | Batch dengan `fasilitas_id IS NULL` (Gudang) |
| admin_dinas       | `null` (Dinas, tanpa faskes)             | Batch dengan `fasilitas_id IS NULL`          |
| puskesmas         | `$user->fasilitasKesehatan->id`          | Batch milik Puskesmas tersebut              |

### 4.5 Role-based Form Default

Form menentukan nilai default `tipe_distribusi` dan opsi `fasilitas_penerima_id` berdasarkan role user:

| Role            | `tipe_distribusi` default       | Opsi Penerima                              |
| --------------- | ------------------------------- | ------------------------------------------ |
| super_admin     | `dinas_ke_puskesmas`            | Semua puskesmas aktif                      |
| admin_gudang    | `dinas_ke_puskesmas`            | Semua puskesmas aktif                      |
| puskesmas       | `puskesmas_ke_pustu`            | Hanya pustu di bawah Puskesmas-nya         |

> **Catatan:** admin_dinas **tidak bisa membuat distribusi** (tidak punya `create_distribusi_obat`). Pustu juga tidak bisa membuat distribusi.

Field `nomor_surat_jalan` di-`disabled()` untuk non-super_admin agar tidak bisa diedit manual.

### 4.6 Mutasi Data Before Create

Di `mutateFormDataBeforeCreate()`, data auto-dilengkapi sebelum disimpan:

```php
// 1. Auto-generate nomor surat jalan (fallback)
if (blank($data['nomor_surat_jalan'])) {
    $data['nomor_surat_jalan'] = $this->generateNomorSuratJalan();
}

// 2. Set pengirim_id = user yang login
$data['pengirim_id'] = $user->id;

// 3. Set fasilitas_pengirim_id + tipe_distribusi berdasarkan role
if (filled($user->fasilitas_kesehatan_id)) {
    // Puskesmas (punya faskes) → distribusi ke Pustu
    $data['fasilitas_pengirim_id'] ??= $userFaskes->id;
    $data['tipe_distribusi'] ??= 'puskesmas_ke_pustu';
} else {
    // admin_gudang / super_admin (Dinas, tanpa faskes) → distribusi ke Puskesmas
    $data['fasilitas_pengirim_id'] ??= null;
    $data['tipe_distribusi'] ??= 'dinas_ke_puskesmas';
}

// 4. Default status = draft
$data['status'] ??= 'draft';
```

---

## 5. Form Schema (`DistribusiObatForm.php`)

Menggunakan **server-driven UI** dengan pattern `static configure(Schema): Schema`.

### Grid Header (3 kolom)

| Field                | Type        | Keterangan                                    |
| -------------------- | ----------- | --------------------------------------------- |
| `nomor_surat_jalan`  | TextInput   | Auto-generate, disabled (non-super_admin)     |
| `tanggal_kirim`      | DatePicker  | Default: `now()`                              |
| `tipe_distribusi`    | Select      | Disabled, default berdasarkan role            |

### Field Individual

| Field                  | Type        | Keterangan                                         |
| ---------------------- | ----------- | -------------------------------------------------- |
| `permintaan_id`        | Select      | Relationship: `permintaan` (status=disetujui), preload, searchable |
| `fasilitas_penerima_id`| Select      | Opsi dinamis berdasarkan role                      |
| `status`               | Select      | `draft` / `dalam_pengiriman`, default: `draft`     |

### Repeater: Detail Obat (table mode)

| Kolom       | Tipe Komponen | Keterangan                                |
| ----------- | ------------- | ----------------------------------------- |
| Pilih Obat  | Select        | Semua obat aktif, `live()` → reset batch  |
| Pilih Batch | Select        | FEFO, filter by faskes pengirim           |
| Jumlah      | TextInput     | Numeric, min:1, default:1                 |

**Logic penting:**
- `obat_id` punya `afterStateUpdated(fn ($set) => $set('batch_id', null))` — reset batch saat ganti obat
- `batch_id` menggunakan `options(fn (Get $get, $livewire): array => self::getBatchOptions(...))` — load dinamis per item

---

## 6. Tabel & Filter (`DistribusiObatsTable.php`)

### Kolom

| Kolom                      | Type     | Keterangan                         |
| -------------------------- | -------- | ---------------------------------- |
| `nomor_surat_jalan`        | Text     | Sortable, searchable               |
| `tipe_distribusi`          | Badge    | `info` = Puskesmas→Pustu, `warning` = Dinas→Puskesmas |
| `fasilitasPengirim.nama`   | Text     | Sortable, searchable               |
| `fasilitasPenerima.nama`   | Text     | Sortable, searchable               |
| `status`                   | Badge    | `gray`=draft, `warning`=pengiriman, `success`=diterima, `danger`=ditolak |
| `tanggal_kirim`            | Date     | Sortable                           |
| `details_count`            | Count    | Jumlah item obat via `counts()`    |
| `pengirim.name`            | Text     | Toggleable, default hidden         |
| `created_at`               | DateTime | Toggleable, default hidden         |

### Filters (Above Content)

- **Tipe Distribusi** — SelectFilter: `puskesmas_ke_pustu` / `dinas_ke_puskesmas`
- **Pengirim** — SelectFilter (searchable): semua `FasilitasKesehatan`
- **Penerima** — SelectFilter (searchable): semua `FasilitasKesehatan`

### Row Actions

| Action           | Keterangan                                |
| ---------------- | ----------------------------------------- |
| Lihat Detail     | Navigasi ke halaman detail (Infolist)     |
| Edit             | Edit record (policy-dependent)            |
| Cetak Faktur     | Buka PDF faktur di tab baru (visible: status ≠ draft) |

### Tabs (List Page)

- **Semua**
- **Dalam Pengiriman** — `status = dalam_pengiriman`
- **Diterima** — `status = diterima`
- **Ditolak** — `status = ditolak`

---

## 7. Pages

| Page                   | File                                            | Keterangan                                         |
| ---------------------- | ----------------------------------------------- | -------------------------------------------------- |
| ListDistribusiObats    | `Pages/ListDistribusiObats.php`                 | Daftar distribusi + tabs + create button            |
| CreateDistribusiObat   | `Pages/CreateDistribusiObat.php`                | Create dengan pre-fill + auto-generate nomor        |
| DetailDistribusi       | `Pages/DetailDistribusi.php`                    | Infolist detail distribusi + retur terkait          |
| EditDistribusiObat     | `Pages/EditDistribusiObat.php`                  | Edit record + cetak faktur + delete                 |

---

## 8. Permission & Policy

### Permission

Semua permission `distribusi_obat` dibuat oleh `RoleAndPermissionSeeder` via loop:

```
view_distribusi_obat
create_distribusi_obat
update_distribusi_obat
delete_distribusi_obat
```

### Assignment ke Role

| Permission                  | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| --------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_distribusi_obat`      |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create_distribusi_obat`    |     ✅      |     ✅       |     ❌      |    ✅     |  ❌   |
| `update_distribusi_obat`    |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `delete_distribusi_obat`    |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |

### Policy Rules (`DistribusiObatPolicy`)

| Method   | super_admin | admin_gudang (Dinas)           | admin_dinas          | puskesmas                                    | pustu                                    |
| -------- | ----------- | ------------------------------ | -------------------- | -------------------------------------------- | ---------------------------------------- |
| `viewAny`| ✅          | ✅ (hasPermission)             | ✅ (hasPermission)   | ✅ (hasPermission)                           | ✅ (hasPermission)                       |
| `view`   | ✅ (semua)  | ✅ (semua, Dinas tanpa faskes) | ✅ (semua)           | ✅ (pengirim/penerima = faskes)              | ✅ (penerima = faskes)                   |
| `create` | ✅          | ✅                             | ❌                   | ✅ (punya faskes)                            | ❌                                       |
| `update` | ✅ (semua)  | ✅ (milik sendiri by user id, draft/pengiriman) | ✅ (semua, oversight) | ✅ sebagai pengirim: draft/pengiriman; sebagai penerima: pengiriman (konfirmasi terima) | ✅ (penerima + dalam_pengiriman) |
| `delete` | ✅ (semua)  | ❌ (no permission)             | ❌                   | ✅ (milik sendiri + draft)                   | ❌                                       |

**Scope view untuk non-super_admin:**
```php
// admin_gudang (Dinas, null faskes): view semua distribusi
// admin_dinas (null faskes): view semua distribusi
// puskesmas: distribusi di mana faskesnya sebagai pengirim atau penerima
// pustu: hanya distribusi yang ditujukan ke faskesnya (sebagai penerima)
```

---

## 9. Alur Status

```
               ┌─────────┐
               │  draft  │
               └────┬────┘
                    │ (Kirim)
                    ▼
        ┌───────────────────┐
        │ dalam_pengiriman  │
        └────────┬──────────┘
                 │
       ┌────────┴────────┐
       ▼                 ▼
┌──────────┐     ┌──────────┐
│ diterima │     │ ditolak  │
└──────────┘     └──────────┘
(Status Final)
```

| Status             | Bisa diedit? | Bisa dihapus? | Keterangan                                  |
| ------------------ | :----------: | :-----------: | ------------------------------------------- |
| `draft`            |     ✅       |     ✅        | Hanya oleh pengirim (admin_gudang/puskesmas) |
| `dalam_pengiriman` |     ✅       |     ❌        | Bisa diedit pengirim atau dikonfirmasi penerima |
| `diterima`         |     ❌       |     ❌        | Status final                                 |
| `ditolak`          |     ❌       |     ❌        | Status final                                 |

**Transisi status di-update oleh actions di halaman edit/detail:**

- **Simpan** → status tetap `draft`
- **Kirim** → status berubah ke `dalam_pengiriman`
- **Konfirmasi Terima** → status berubah ke `diterima`
- **Tolak** → status berubah ke `ditolak`

---

## 10. Cetak Faktur PDF

**Route:** `GET /admin/distribusi/{distribusi}/cetak-faktur`
**Controller:** `CetakFakturController@__invoke`

**Alur:**
1. Halaman detail/tabel distribusi → klik "Cetak Faktur"
2. Redirect ke route `admin.distribusi.cetak-faktur`
3. Controller load distribusi + relasi (`fasilitasPengirim`, `fasilitasPenerima`, `details.obat.satuan`, `details.batch`, `pengirim`)
4. Generate PDF via `spatie/laravel-pdf` dengan view `pdf.faktur-distribusi`
5. Render sebagai PDF A4, ditampilkan inline di browser

**Visibility:** Tombol "Cetak Faktur" hanya muncul saat status ≠ `draft`.

---

## 11. Cara Penggunaan

### Membuat Distribusi dari Permintaan

1. Buka halaman **Detail Permintaan Obat** (status harus `disetujui`)
2. Klik tombol **"Buat Distribusi"** (hanya untuk role yang punya `create_distribusi_obat`: `admin_gudang` untuk distribusi Dinas→Puskesmas, `puskesmas` untuk distribusi Puskesmas→Pustu)
3. Form create distribusi akan terbuka dengan data terisi otomatis:
   - **Nomor Surat Jalan** — auto-generate
   - **Tanggal Kirim** — default hari ini
   - **Tipe Distribusi** — auto (Puskesmas→Pustu atau Dinas→Puskesmas)
   - **Permintaan Terkait** — terisi nomor permintaan
   - **Penerima** — terisi peminta (pustu)
   - **Detail Obat** — terisi obat dan jumlah yang disetujui
4. Pilih **Batch** untuk setiap item obat (FEFO — batch terdekat expired paling atas)
5. Sesuaikan **Jumlah** jika perlu
6. Klik:
   - **"Simpan"** → status `draft` (bisa diedit lagi)
   - **"Kirim"** → status `dalam_pengiriman`

### Membuat Distribusi Manual (tanpa permintaan)

1. Buka **Distribusi Obat** → **Buat Distribusi**
2. Isi form manual:
   - Pilih permintaan terkait (opsional, `permintaan_id` nullable sejak migration terakhir)
   - Pilih penerima
   - Tambah item obat + pilih batch
3. Simpan atau Kirim

### Melihat Detail Distribusi

Klik **"Lihat Detail"** pada baris distribusi:

**Infolist sections:**
- **Informasi Distribusi** — nomor, tipe (badge), status (badge)
- **Pengirim & Penerima** — fasilitas pengirim, penerima, dibuat oleh, diterima oleh
- **Detail Obat** — tabel obat dengan batch, expired, jumlah (RepeatableEntry)
- **Permintaan Terkait** — link ke permintaan (jika ada)
- **Retur Terkait** — daftar retur (jika ada)
- **Informasi Waktu** — tanggal kirim, terima, created_at, updated_at
- **Catatan** — catatan distribusi (jika ada)

### Mencetak Faktur

1. Buka detail atau daftar distribusi
2. Klik **"Cetak Faktur"** (hanya untuk status ≠ draft)
3. PDF akan terbuka di tab baru

---

## 12. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/DistribusiObats/DistribusiObatResource.php`
- `app/Filament/Resources/DistribusiObats/Pages\CreateDistribusiObat.php`
- `app/Filament/Resources/DistribusiObats/Pages\DetailDistribusi.php`
- `app/Filament/Resources/DistribusiObats/Pages\EditDistribusiObat.php`
- `app/Filament/Resources/DistribusiObats/Pages\ListDistribusiObats.php`
- `app/Filament/Resources/DistribusiObats/Schemas\DistribusiObatForm.php`
- `app/Filament/Resources/DistribusiObats/Tables\DistribusiObatsTable.php`
- `app/Filament/Resources/DistribusiObats/Widgets\DistribusiObatStatsOverview.php`
- `app/Models/DistribusiObat.php`
- `app/Policies/DistribusiObatPolicy.php`
