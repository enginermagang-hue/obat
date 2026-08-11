# Activity Log — Dokumentasi Fitur

## 1. Tujuan

Menyediakan **audit trail** (jejak aktivitas) untuk semua perubahan data di sistem. Setiap event CRUD, approval, rejection, dan aksi penting lainnya dicatat secara otomatis oleh `spatie/laravel-activitylog`. Log hanya bisa dilihat (read-only) — tidak ada create, update, atau delete manual.

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ❌ |
| Table Config | ✅ |

## 3. Detail Teknis

### Model

**Model:** `App\Models\ActivityLog` (extends `Spatie\Activitylog\Models\Activity`)  
**Table:** `activity_log`  

Model ini adalah custom model yang memperpanjang model Spatie Activitylog, sehingga Filament bisa auto-discover policy dan menyediakan type-safe relations.

### Kategori Log (`log_name`)

| Kategori             | Warna Badge | Sumber Data                          |
| -------------------- | ----------- | ------------------------------------ |
| `auth`               | warning     | Login, logout, failed login          |
| `master_data`        | gray        | CRUD obat, faskes, supplier, dll     |
| `permintaan_obat`    | info        | Permintaan obat                      |
| `distribusi_obat`    | info        | Distribusi obat                      |
| `retur_obat`         | success     | Retur obat                           |
| `penerimaan_stok`    | success     | Penerimaan stok                      |
| `opname_stok`        | danger      | Stok opname                          |
| `laporan_lplpo`      | primary     | Laporan LPLPO                        |
| `laporan_rko`        | primary     | Laporan RKO                          |
| `laporan_neraca`     | primary     | Neraca Tahunan                       |
| `user_management`    | primary     | Manajemen user                       |

### Event yang Dicatat

| Event           | Warna   | Keterangan                       |
| --------------- | ------- | -------------------------------- |
| `login`         | success | User login                       |
| `logout`        | warning | User logout                      |
| `failed_login`  | danger  | Percobaan login gagal            |
| `created`       | success | Record baru dibuat               |
| `updated`       | info    | Record diedit                    |
| `deleted`       | danger  | Record dihapus                   |
| `approved`      | success | Persetujuan (permintaan, retur)  |
| `rejected`      | danger  | Penolakan                        |
| `completed`     | success | Selesai (retur, opname)          |
| `received`      | success | Diterima (distribusi)            |
| `generated`     | primary | Generate otomatis                |
| `role_updated`  | info    | Role user diubah                 |

### Tabel & Filter (`ActivityLogsTable.php`)

| Kolom           | Type     | Keterangan                              |
| --------------- | -------- | --------------------------------------- |
| `log_name`      | Badge    | Kategori log dengan warna khusus        |
| `event`         | Badge    | Tipe event dengan warna khusus          |
| `description`   | Text     | Deskripsi aktivitas (wrap, limit 80)    |
| `causer.name`   | Text     | User yang melakukan aksi (toggleable)   |
| `subject_type`  | Text     | Model subjek, toggleable hidden default |
| `created_at`    | DateTime | Format `d/m/Y H:i`, sortable            |

**Default sort:** `created_at DESC`

**Filters:**
- **Jenis Log** — SelectFilter (multiple): auth, master_data, permintaan_obat, dll
- **Event** — SelectFilter (multiple): login, created, updated, deleted, dll

### Hak Akses

| Permission                  | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| --------------------------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_activity_logs`        |     ✅      |     ✅       |     ✅      |    ✅     |  ✅   |
| `create/update/delete`      |     ❌      |     ❌       |     ❌      |    ❌     |  ❌   |

> Semua role bisa melihat log (view only). Tidak ada yang bisa membuat, mengedit, atau menghapus log manual.

### Policy Rules (`ActivityLogPolicy`)

| Method   | Aturan                                    |
| -------- | ----------------------------------------- |
| `viewAny`| ✅ (hasPermission)                        |
| `view`   | ✅ (hasPermission)                        |
| `create` | ❌ (false, tidak bisa create manual)       |
| `update` | ❌ (false, tidak bisa edit)               |
| `delete` | ❌ (false, tidak bisa hapus)              |

## 4. Cara Penggunaan

1. Buka **Sistem** → **Log Aktivitas**
2. Gunakan filter untuk mencari berdasarkan kategori atau event
3. Klik baris untuk melihat detail aktivitas (subject type, subject ID, user, timestamp)
4. Log tidak bisa dihapus atau diedit — untuk pembersihan, gunakan command artisan atau kebijakan retensi database

## 5. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/ActivityLogs/ActivityLogResource.php`
- `app/Filament/Resources/ActivityLogs/Pages\ListActivityLogs.php`
- `app/Filament/Resources/ActivityLogs/Tables\ActivityLogsTable.php`
- `app/Models/ActivityLog.php`
- `app/Policies/ActivityLogPolicy.php`
