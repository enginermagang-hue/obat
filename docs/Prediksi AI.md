# Prediksi AI — Dokumentasi Fitur

## 1. Tujuan

Mengaplikasikan **machine learning** (Rubix/ML) dan **statistical forecasting** (moving average) untuk memprediksi kebutuhan obat per fasilitas kesehatan (Puskesmas/Pustu) secara periodik, membantu pengisian **RKO (Rencana Kebutuhan Obat)** secara lebih akurat dan berbasis data historis.

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

```
┌────────────────────────┐       ┌─────────────────────────────────┐
│   pemakaian_obat       │──────▶│   PredictionService              │
│   (sumber data utama)  │       │   - GradientBoost (Rubix/ML)     │
│                        │       │   - Serializer: RBX              │
│                        │       │   - MovingAverage (fallback)     │
└────────────────────────┘       └──────────┬──────────────────────┘
                                            │
                            ┌───────────────┴───────────────┐
                            ▼                               ▼
                   ┌──────────────────┐          ┌──────────────────┐
                   │  model_prediksi  │          │ prediksi_kebutuhan│
                   │  (serialized ML  │          │ (hasil prediksi   │
                   │   model di DB)   │          │  3 bln ke depan)  │
                   └──────────────────┘          └────────┬─────────┘
                                                          │
                                             ┌────────────┘
                                             ▼
                                    ┌──────────────────┐
                                    │  detail_rko       │
                                    │  .prediksi_id     │
                                    │  → auto-fill      │
                                    │    usulan dari AI  │
                                    └──────────────────┘
```

## 4. Komponen yang Dibangun

### 4.1 Foundation

| #   | Komponen | File                                   | Keterangan                            |
| --- | -------- | -------------------------------------- | ------------------------------------- |
| 1   | Package  | `composer require rubix/ml:^2.5`       | ML engine (v2.5.3 terinstall)         |
| 2   | Model    | `app/Models/ModelPrediksi.php`         | Trained model per faskes+obat         |
| 3   | Model    | `app/Models/PrediksiKebutuhan.php`     | Hasil prediksi per periode            |
| 4   | Policy   | `app/Policies/ModelPrediksiPolicy.php` | Gate model_prediksi                   |
| 5   | Policy   | `app/Policies/PrediksiKebutuhanPolicy.php` | Gate prediksi_kebutuhan           |

**Relasi tambahan pada model existing:**

| Model                  | Method                     | Type     |
| ---------------------- | -------------------------- | -------- |
| `FasilitasKesehatan`   | `modelPrediksi()`          | HasMany  |
| `FasilitasKesehatan`   | `prediksiKebutuhan()`      | HasMany  |
| `Obat`                 | `modelPrediksi()`          | HasMany  |
| `Obat`                 | `prediksiKebutuhan()`      | HasMany  |
| `DetailRko`            | `prediksi()`               | BelongsTo (ke PrediksiKebutuhan) |

### 4.2 Prediction Engine

| #   | Komponen | File                                       | Keterangan                                    |
| --- | -------- | ------------------------------------------ | --------------------------------------------- |
| 6   | Service  | `app/Services/PredictionService.php`       | train(), generatePredictions(), getMonthlyUsage() |
| 7   | Service  | `app/Services/MovingAverageService.php`    | predict(), hasSufficientData()                |
| 8   | Command  | `app/Console/Commands/AiTrainModels.php`   | `php artisan ai:train-models`                   |

#### `PredictionService` API

```php
// Train model untuk kombinasi faskes + obat
train(FasilitasKesehatan $faskes, Obat $obat): ModelPrediksi

// Generate prediksi 3 bulan ke depan dari model yg sudah di-train
generatePredictions(ModelPrediksi $model): Collection<int, PrediksiKebutuhan>

// Ambil data pemakaian bulanan (public, reusable)
getMonthlyUsage(int $fasilitasId, int $obatId): array
```

**Feature vector (9 fitur):**

| Index | Fitur            | Keterangan                                 |
| :---: | ---------------- | ------------------------------------------ |
| 0     | `lag_1_bulan`    | Pemakaian bulan sebelumnya (t-1)           |
| 1     | `lag_2_bulan`    | Pemakaian 2 bulan sebelumnya (t-2)         |
| 2     | `lag_3_bulan`    | Pemakaian 3 bulan sebelumnya (t-3)         |
| 3     | `rata_rata_6_bulan` | Rata-rata pemakaian 6 bulan terakhir      |
| 4     | `rata_rata_12_bulan` | Rata-rata pemakaian 12 bulan terakhir    |
| 5     | `bulan`          | Nomor bulan (1-12) untuk pola musiman      |
| 6     | `trend_3_bulan`  | Slope linear 3 bulan terakhir              |
| 7     | `stok_saat_ini`  | Stok saat ini dari stok_faskes/gudang      |
| 8     | `tipe_faskes`    | 1=puskesmas, 0=pustu                       |

**Model serialization:** Rubix/ML RBX serializer → disimpan di kolom `model_data` (LONGTEXT).

**Fallback:** Jika data < 6 bulan → status `data_belum_cukup` → prediksi via MovingAverageService (rata-rata 3 bulan terakhir + confidence interval berdasarkan std deviasi).

#### `MovingAverageService` API

```php
predict(array $monthlyData, int $window = 3): array{jumlah, confidence_lower, confidence_upper}
hasSufficientData(array $monthlyData, int $minimumMonths = 3): bool
```

### 4.3 Filament Admin Panel

| #   | Resource              | Lokasi Files                                  | Menu di Panel                  |
| --- | --------------------- | --------------------------------------------- | ------------------------------ |
| 9   | Model Prediksi        | `app/Filament/Resources/ModelPrediksis/`      | Prediksi AI → Model Prediksi   |
| 10  | Hasil Prediksi        | `app/Filament/Resources/PrediksiKebutuhans/`  | Prediksi AI → Hasil Prediksi   |
| 11  | DetailRkoForm (update) | `app/Filament/Resources/LaporanRkos/Schemas/LaporanRkoForm.php` | Auto-fill saat pilih obat |

**Kolom ModelPrediksiResource:**
- Fasilitas, Obat, Status (badge), Akurasi R² (%), Tanggal Training, Jumlah Data

**Action per baris — "Train Model":**
- Tombol hijau (`heroicon-o-play`) dengan konfirmasi
- Menjalankan `php artisan ai:train-models --fasilitas-id=X --obat-id=Y --force`
- Menampilkan notifikasi sukses/gagal
- Hanya untuk user dengan permission `create_model_prediksi` (super_admin)

**Kolom PrediksiKebutuhanResource:**
- Fasilitas, Obat, Bulan (nama), Tahun, Prediksi, Metode (badge), CI

**Auto-fill di DetailRkoForm:**
Saat user memilih obat di form RKO, sistem otomatis:
1. Mencari `prediksi_kebutuhan` untuk `fasilitas_id` + `obat_id` + `tahun` yang sesuai
2. Jika ditemukan → mengisi `usulan` + `keterangan` dengan format: `"Prediksi AI: 500 (range: 450 - 550)"`
3. User tetap bisa edit manual

### 4.4 Automation & Testing

| #   | Komponen                           | Keterangan                                   |
| --- | ---------------------------------- | -------------------------------------------- |
| 12  | Schedule `routes/console.php`      | `AiTrainModels` setiap Minggu jam 02:00 WITA |
| 13  | Permission di `RoleAndPermissionSeeder` | Lihat tabel §6                          |
| 14  | Tests                              | Passing (lihat §8)                           |

---

## 5. Detail Model & Relasi

### `ModelPrediksi`

| Kolom                | Tipe              | Nullable | Keterangan                            |
| -------------------- | ----------------- | -------- | ------------------------------------- |
| id                   | BIGINT (PK)       |          |                                       |
| fasilitas_id         | BIGINT (FK)       |          | FK → fasilitas_kesehatan              |
| obat_id              | BIGINT (FK)       |          | FK → obat                             |
| model_data           | LONGTEXT          |          | Serialized Rubix/ML model (RBX)       |
| akurasi_r2           | DECIMAL(5,4)      | ✓        | R² score 0-1                          |
| tanggal_training     | DATE              |          | Tanggal terakhir training             |
| data_training_count  | INT               |          | Jumlah bulan data yang dipakai        |
| fitur_digunakan      | JSON              | ✓        | Daftar nama fitur                     |
| status               | ENUM              |          | `aktif`, `kadaluarsa`, `gagal`, `data_belum_cukup` |
| created_at           | TIMESTAMP         | ✓        |                                       |
| updated_at           | TIMESTAMP         | ✓        |                                       |

Unique: `[fasilitas_id, obat_id]`

### `PrediksiKebutuhan`

| Kolom              | Tipe              | Nullable | Keterangan                            |
| ------------------ | ----------------- | -------- | ------------------------------------- |
| id                 | BIGINT (PK)       |          |                                       |
| fasilitas_id       | BIGINT (FK)       |          | FK → fasilitas_kesehatan              |
| obat_id            | BIGINT (FK)       |          | FK → obat                             |
| model_id           | BIGINT (FK)       | ✓        | FK → model_prediksi                   |
| periode_bulan      | INT               |          | 1-12                                  |
| periode_tahun      | INT               |          |                                       |
| jumlah_prediksi    | INT               |          | Hasil prediksi                        |
| confidence_lower   | INT               | ✓        | Batas bawah CI 95%                    |
| confidence_upper   | INT               | ✓        | Batas atas CI 95%                     |
| metode             | ENUM              |          | `ai_gradient_boost`, `moving_average`, `manual` |
| dibuat_oleh        | BIGINT (FK)       | ✓        | FK → users (NULL = system-generated)  |
| catatan            | TEXT              | ✓        | "Prediksi AI: ... (range: ...)"       |
| created_at         | TIMESTAMP         | ✓        |                                       |
| updated_at         | TIMESTAMP         | ✓        |                                       |

Unique: `[fasilitas_id, obat_id, periode_bulan, periode_tahun]`

---

## 6. Permission Assignment

| Permission                              | super_admin | admin_gudang | admin_dinas | user |
| --------------------------------------- | :---------: | :----------: | :---------: | :--: |
| `view_model_prediksi`                   |     ✅      |      ✅      |     ✅      |  ❌  |
| `create/update/delete_model_prediksi`   |     ✅      |      ❌      |     ❌      |  ❌  |
| `view_prediksi_kebutuhan`               |     ✅      |      ✅      |     ✅      |  ✅  |
| `create/update/delete_prediksi_kebutuhan` |     ✅      |      ❌      |     ❌      |  ❌  |

> **Note:** Training hanya via `php artisan ai:train-models` (cron atau manual). Untuk role non-super_admin, semua resource prediksi bersifat read-only.

---

## 7. Alur Training (Cron Mingguan)

```
php artisan ai:train-models
  │
  ├─ Options:
  │   --fasilitas-id=   → training untuk satu faskes saja
  │   --obat-id=        → training untuk satu obat saja
  │   --force           → retrain meski model sudah aktif
  │
  ├─ 1. Ambil semua kombinasi faskes+obat dari pemakaian_obat (distinct)
  │     (max 500 kombinasi per run)
  │
  ├─ 2. Untuk setiap kombinasi:
  │     ├─ a. Extract data pemakaian 12 bulan terakhir
  │     ├─ b. Feature engineering (9 fitur, lihat §4.2)
  │     ├─ c. Jika data >= 6 bulan:
  │     │     - Train GradientBoost (RegressionTree max_depth=3, lr=0.1, subsample=0.8, max_estimators=1000)
  │     │     - Evaluasi dengan R² score
  │     │     - Serialize dengan RBX → simpan ke model_prediksi (status: aktif)
  │     │     - Generate prediksi 3 bulan ke depan
  │     └─ d. Jika data < 6 bulan:
  │           - Simpan model dengan status: data_belum_cukup
  │           - Prediksi via Moving Average 3 bulan (metode: moving_average)
  │
  └─ 3. Tandai model sebelumnya yang aktif sebagai 'kadaluarsa'
```

**Schedule:** Setiap Minggu jam 02:00 (via `routes/console.php`)
```php
Schedule::command(\App\Console\Commands\AiTrainModels::class)
    ->weekly()->sundays()->at('02:00')
    ->withoutOverlapping()->runInBackground();
```

---

## 8. Alur Integrasi RKO

### 8.1 Auto-fill saat Tambah Item Manual

```
User buka halaman Create/Edit RKO
  → Pilih faskes (auto-fill dari user, disabled untuk user faskes)
  → Pilih sumber dana & periode tahun
  → Tambah item obat via Repeater:
     1. Pilih obat dari Select
     2. → afterStateUpdated trigger:
        a. Set harga_perkiraan dari obat.harga_satuan
        b. Cari prediksi_kebutuhan WHERE fasilitas_id + obat_id + periode_tahun
        c. Jika ditemukan:
           - Set keterangan = "Prediksi AI: {jumlah} (range: {cl} - {cu})"
        d. User tetap bisa edit semua field manual
     3. Hitung total_harga = usulan × harga_perkiraan
```

### 8.2 Generate dari Prediksi (Bulk)

```
User buka Create RKO
  → Klik tombol "Generate dari Prediksi" (icon sparkles, warna info)
  → Konfirmasi dialog → Proses:
     1. Query semua obat aktif (Obat::where('status','aktif'))
     2. Query PrediksiKebutuhan WHERE fasilitas_id + periode_tahun
        → GroupBy obat_id → ambil prediksi bulan terbaru per obat
     3. Untuk setiap obat:
        ├─ Jika ada prediksi:
        │   - rata_rata_pemakaian_bulanan = jumlah_prediksi
        │   - pemakaian_tahun_sebelumnya = jumlah_prediksi × 12
        │   - keterangan = "Prediksi: {n} ({metode}, range: {lo}–{hi})"
        │   - prediksi_id = prediksi->id
        └─ Jika tidak ada prediksi:
            - rata_rata_pemakaian_bulanan = 0
            - pemakaian_tahun_sebelumnya = 0
            - keterangan = null, prediksi_id = null
     4. Ambil stok_akhir dari StokFaskes
     5. Hitung rumus Kemenkes (kebutuhan_tahunan, buffer, dll)
     6. Timpa $this->details[] → flush table
  → Notifikasi: "{n} item obat ({m} dari prediksi)"
```

**Perbedaan dengan "Generate dari Pemakaian":**

| Aspek | Generate dari Pemakaian | Generate dari Prediksi |
| :---- | :---------------------- | :--------------------- |
| Sumber data | RiwayatStok (keluar tahun lalu) | PrediksiKebutuhan (AI/MA) |
| rata_rata_pemakaian_bulanan | sum(keluar) / 12 | jumlah_prediksi |
| pemakaian_tahun_sebelumnya | sum(keluar) aktual | jumlah_prediksi × 12 |
| Item yang di-generate | Obat berdasarkan riwayat/stok | Semua obat aktif |
| prediksi_id | null | FK ke prediksi_kebutuhan |
| Kolom AI | ❌ (minus) | ✅ (sparkles icon) |
| keterangan | null | "Prediksi: {n} ({metode}, range: ...)" |

---

## 9. Testing

```
✓ MovingAverageServiceTest (7 tests, 14 assertions)
  - test_predict_returns_average_of_last_3_months
  - test_predict_returns_zero_for_empty_data
  - test_predict_uses_specified_window
  - test_predict_handles_single_value
  - test_has_sufficient_data_returns_true
  - test_has_sufficient_data_returns_false
  - test_predict_never_returns_negative_jumlah

✓ AiTrainModelsCommandTest (2 tests, 5 assertions)
  - test_command_has_correct_configuration
  - test_command_has_expected_options
```

**Menjalankan tests:**
```bash
php artisan test --compact --filter="MovingAverageServiceTest|AiTrainModelsCommandTest"
```

---

## 10. Cara Penggunaan

### Training Model

```bash
# Training semua kombinasi faskes+obat
php artisan ai:train-models

# Training spesifik
php artisan ai:train-models --fasilitas-id=1
php artisan ai:train-models --obat-id=5
php artisan ai:train-models --fasilitas-id=1 --obat-id=5

# Retrain paksa meski sudah ada model aktif
php artisan ai:train-models --force
```

### Melihat Hasil

1. Buka Filament Admin Panel → menu **Prediksi AI**
2. **Model Prediksi** — Lihat status model per faskes+obat, akurasi R², tanggal training
3. **Hasil Prediksi** — Lihat prediksi per periode, confidence interval, metode

### RKO Auto-Fill

1. Buka Laporan → RKO → Buat RKO baru
2. Pilih faskes, sumber dana, tahun
3. Tambah item → pilih obat → `keterangan` terisi otomatis dari prediksi AI

### RKO Generate dari Prediksi

1. Buka Laporan → RKO → Buat RKO baru
2. Klik tombol **"Generate dari Prediksi"** (icon sparkles, warna biru)
3. Konfirmasi dialog
4. Semua obat aktif terisi, yang punya prediksi otomatis dapat nilai
5. Kolom AI (sparkles) menandai item yang berasal dari prediksi
6. Edit item yang perlu disesuaikan
7. Simpan RKO

---

## 11. Dependencies

```json
{
  "require": {
    "php": "^8.3",
    "rubix/ml": "^2.5"
  }
}
```

Rubix/ML v2.5.3 terinstall dengan dependensi:
- `rubix/tensor` ^3.0 (matrix math)
- `amphp/parallel` ^1.4 (parallel processing)
- `andrewdalpino/okbloomer` ^1.0 (Bloom filter)
- `wamania/php-stemmer` ^4.0 (text processing)

---

## 12. Daftar File (23 files)

### Files Baru

| #   | File Path                                              |
| --- | ------------------------------------------------------ |
| 1   | `app/Models/ModelPrediksi.php`                         |
| 2   | `app/Models/PrediksiKebutuhan.php`                     |
| 3   | `app/Services/MovingAverageService.php`                |
| 4   | `app/Services/PredictionService.php`                   |
| 5   | `app/Console/Commands/AiTrainModels.php`               |
| 6   | `app/Policies/ModelPrediksiPolicy.php`                 |
| 7   | `app/Policies/PrediksiKebutuhanPolicy.php`             |
| 8   | `app/Filament/Resources/ModelPrediksis/ModelPrediksiResource.php` |
| 9   | `app/Filament/Resources/ModelPrediksis/Pages/ListModelPrediksis.php` |
| 10  | `app/Filament/Resources/ModelPrediksis/Schemas/ModelPrediksiForm.php` |
| 11  | `app/Filament/Resources/ModelPrediksis/Tables/ModelPrediksisTable.php` |
| 12  | `app/Filament/Resources/PrediksiKebutuhans/PrediksiKebutuhanResource.php` |
| 13  | `app/Filament/Resources/PrediksiKebutuhans/Pages/ListPrediksiKebutuhans.php` |
| 14  | `app/Filament/Resources/PrediksiKebutuhans/Schemas/PrediksiKebutuhanForm.php` |
| 15  | `app/Filament/Resources/PrediksiKebutuhans/Tables/PrediksiKebutuhansTable.php` |
| 16  | `tests/Unit/MovingAverageServiceTest.php`              |
| 17  | `tests/Feature/AiTrainModelsCommandTest.php`           |

### Files Dimodifikasi

| #   | File Path                                              |
| --- | ------------------------------------------------------ |
| 1   | `app/Models/FasilitasKesehatan.php`                    |
| 2   | `app/Models/Obat.php`                                  |
| 3   | `app/Models/DetailRko.php`                             |
| 4   | `app/Providers/AuthServiceProvider.php`                |
| 5   | `routes/console.php`                                   |
| 6   | `database/seeders/RoleAndPermissionSeeder.php`         |
| 7   | `app/Filament/Resources/LaporanRkos/Schemas/LaporanRkoForm.php` |

### Files Dimodifikasi (Generate dari Prediksi)

| #   | File Path                                              | Perubahan                                      |
| --- | ------------------------------------------------------ | ---------------------------------------------- |
| 1   | `app/Filament/Resources/LaporanRkos/Concerns/ManagesRkoDetails.php` | + generateFromPrediksi(), prediksi_id, kolom AI |
| 2   | `app/Filament/Resources/LaporanRkos/Pages/CreateLaporanRko.php`      | + tombol "Generate dari Prediksi", prediksi_id  |
| 3   | `app/Filament/Resources/LaporanRkos/Pages/EditLaporanRko.php`        | + prediksi_id di fillForm + afterSave           |



## 13. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/PrediksiKebutuhans/Pages\ListPrediksiKebutuhans.php`
- `app/Filament/Resources/PrediksiKebutuhans/PrediksiKebutuhanResource.php`
- `app/Filament/Resources/PrediksiKebutuhans/Schemas\PrediksiKebutuhanForm.php`
- `app/Filament/Resources/PrediksiKebutuhans/Tables\PrediksiKebutuhansTable.php`
- `app/Models/PrediksiKebutuhan.php`
- `app/Policies/PrediksiKebutuhanPolicy.php`

